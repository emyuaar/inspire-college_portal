<?php

namespace App\Services;

use App\Models\LearnerDiagnosticLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class LearnerLogger
{
    /**
     * Start a new log entry.
     */
    public static function log(string $eventName, string $category = 'activity')
    {
        return new LearnerLoggerBuilder($eventName, $category);
    }

    /**
     * Log a caught exception with full enrichment.
     */
    public static function logException(Throwable $e, string $eventName = 'learner.exception', string $category = 'error')
    {
        return self::log($eventName, $category)
            ->status('failed')
            ->severity('error')
            ->withException($e);
    }

    /**
     * Log a validation failure.
     */
    public static function logValidationFailure(array $errors, string $eventName = 'learner.validation.failed')
    {
        return self::log($eventName, 'validation')
            ->status('failed')
            ->severity('warning')
            ->validationErrors($errors)
            ->humanMessage('Form validation failed.');
    }
}

class LearnerLoggerBuilder
{
    protected array $data = [];

    public function __construct(string $eventName, string $category)
    {
        $this->data['event_type'] = $eventName;
        $this->data['event_category'] = $category;
        
        $this->populateDefaults();
    }

    protected function populateDefaults()
    {
        $request = Request::instance();
        
        // 1. Request Context
        if ($request) {
            $this->data['url'] = $request->fullUrl();
            if ($route = Route::current()) {
                $this->data['route_name'] = $route->getName();
                $this->data['controller_action'] = $route->getActionName();
                $this->inferContextFromRoute($route);
            }
            
            $this->data['http_method'] = $request->method();
            $this->data['ip_address'] = $request->ip();
            $this->data['user_agent'] = $request->userAgent();
            $this->data['referrer'] = $request->headers->get('referer');
            $this->data['session_id'] = $request->hasSession() ? $request->session()->getId() : null;
            
            // Determine request type
            $this->data['request_type'] = $this->determineRequestType($request);
            
            // Parse User Agent for Browser/OS/Device if not already set
            $this->parseUserAgent($request->userAgent());
        }

        // 2. Identity Context
        $this->resolveIdentity();

        // 3. System Context
        $this->data['correlation_id'] = $this->getCorrelationId();
        $this->data['action_status'] = 'success';
        $this->data['severity'] = 'info';
        $this->data['learner_initiated'] = true;

        // 4. Time tracking
        if (app()->bound('learner_request_start_time')) {
            $this->data['elapsed_time_ms'] = (microtime(true) - app('learner_request_start_time')) * 1000;
        }
    }

    protected function determineRequestType($request): string
    {
        if ($request->is('api/*')) return 'api';
        if ($request->ajax() || $request->wantsJson()) return 'ajax';
        if ($request->hasFile('*') || str_contains($request->header('Content-Type', ''), 'multipart/form-data')) return 'upload';
        
        return 'page';
    }

    protected function inferContextFromRoute($route)
    {
        // Pluck IDs from common route parameters (handle both ID and Model)
        $params = $route->parameters();
        
        $resolveId = function($val) {
            return is_object($val) ? ($val->id ?? null) : $val;
        };

        $this->data['course_id'] = $resolveId($params['course'] ?? $params['course_id'] ?? $this->data['course_id'] ?? null);
        $this->data['module_id'] = $resolveId($params['module'] ?? $params['module_id'] ?? $this->data['module_id'] ?? null);
        $this->data['lesson_id'] = $resolveId($params['lesson'] ?? $params['lesson_id'] ?? $this->data['lesson_id'] ?? null);
        $this->data['assignment_id'] = $resolveId($params['assignment'] ?? $params['assignment_id'] ?? $this->data['assignment_id'] ?? null);
        
        // Handle names and specific logic for enrolment
        foreach (['course', 'module', 'lesson', 'assignment', 'enrolment'] as $key) {
            if (isset($params[$key]) && is_object($params[$key])) {
                $model = $params[$key];
                
                if ($key === 'enrolment') {
                    $this->data['course_id'] = $model->course_id ?? $this->data['course_id'];
                    $this->data['learner_id'] = $model->learner_id ?? $this->data['learner_id'];
                }

                if ($key === 'assignment') {
                    $this->data['course_id'] = $model->course_id ?? $this->data['course_id'];
                    $this->data['module_id'] = $model->module_id ?? $this->data['module_id'];
                }

                if ($key === 'lesson') {
                    $this->data['module_id'] = $model->module_id ?? $this->data['module_id'];
                }
                
                // Pluck names if available and not already set
                if (method_exists($model, 'getAttribute') && (isset($model->title) || isset($model->name))) {
                    $field = ($key === 'enrolment') ? 'course_name' : $key . '_name';
                    if (empty($this->data[$field])) {
                        $this->data[$field] = $model->title ?? $model->name;
                    }
                }
            }
        }
    }

    protected function resolveIdentity()
    {
        // Guards to check
        $guards = ['web', 'learner'];
        $user = null;

        foreach ($guards as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            } catch (Throwable $e) {}
        }

        if ($user) {
            $this->data['portal_user_id'] = $user->id;
            
            // Check if it's a learner based on ID/OrgID logic or explicit role
            $isLearner = false;
            if (get_class($user) === 'App\Models\User') {
                $isLearner = ($user->org_id !== $user->id);
            } elseif (isset($user->role) && in_array(strtolower($user->role), ['learner', 'student'])) {
                $isLearner = true;
            }

            if ($isLearner) {
                $this->data['learner_id'] = $user->id;
            }

            $this->data['learner_name'] = $user->full_name ?? $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? ''));
            $this->data['learner_email'] = $user->email_address ?? $user->email ?? null;
        }
    }

    protected function parseUserAgent(?string $ua)
    {
        if (!$ua) return;
        
        // Simple manual parsing for common ones to avoid heavy dependencies
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone')) {
            $this->data['device_type'] = 'mobile';
        } else {
            $this->data['device_type'] = 'desktop';
        }

        if (str_contains($ua, 'Windows')) $this->data['os'] = 'Windows';
        elseif (str_contains($ua, 'Macintosh')) $this->data['os'] = 'MacOS';
        elseif (str_contains($ua, 'Android')) $this->data['os'] = 'Android';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $this->data['os'] = 'iOS';
        else $this->data['os'] = 'Linux/Other';

        if (str_contains($ua, 'Chrome')) $this->data['browser'] = 'Chrome';
        elseif (str_contains($ua, 'Firefox')) $this->data['browser'] = 'Firefox';
        elseif (str_contains($ua, 'Safari')) $this->data['browser'] = 'Safari';
        elseif (str_contains($ua, 'Edge')) $this->data['browser'] = 'Edge';
    }

    public function getCorrelationId()
    {
        if (app()->bound('learner_request_correlation_id')) {
            return app('learner_request_correlation_id');
        }
        
        $id = (string) \Illuminate\Support\Str::uuid();
        app()->instance('learner_request_correlation_id', $id);
        return $id;
    }

    // --- Fluent Setters ---

    public function status(string $status)
    {
        $this->data['action_status'] = $status;
        return $this;
    }

    public function severity(string $severity)
    {
        $this->data['severity'] = $severity;
        return $this;
    }

    public function courseId($id) { $this->data['course_id'] = $id; return $this; }
    public function moduleId($id) { $this->data['module_id'] = $id; return $this; }
    public function lessonId($id) { $this->data['lesson_id'] = $id; return $this; }
    public function assignmentId($id) { $this->data['assignment_id'] = $id; return $this; }
    
    public function humanMessage(?string $msg) { $this->data['message_human'] = $msg; return $this; }
    public function technicalMessage(?string $msg) { $this->data['message_technical'] = $msg; return $this; }
    public function statusCode(int $code) { $this->data['response_status_code'] = $code; return $this; }

    public function validationErrors(?array $errors)
    {
        $this->data['validation_errors'] = $errors;
        return $this;
    }

    public function withException(Throwable $e)
    {
        $this->data['exception_class'] = get_class($e);
        $this->data['exception_message'] = $e->getMessage();
        $this->data['stack_trace'] = $e->getTraceAsString();
        $this->status('failed');
        
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $this->validationErrors($e->errors());
            $this->severity('warning');
            $this->data['event_category'] = 'validation';
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $this->statusCode($e->getStatusCode());
            if ($e->getStatusCode() === 403 || $e->getStatusCode() === 401) {
                $this->blocked(true);
            }
        }
        
        return $this;
    }

    public function file(?string $name, ?string $mime = null, ?int $size = null)
    {
        $this->data['file_name'] = $name;
        $this->data['mime_type'] = $mime;
        $this->data['file_size'] = $size;
        return $this;
    }

    public function context(array $context)
    {
        $this->data['additional_context'] = array_merge($this->data['additional_context'] ?? [], $context);
        return $this;
    }

    public function blocked(bool $blocked = true)
    {
        $this->data['blocked_by_permission'] = $blocked;
        if ($blocked) {
            $this->status('blocked')->severity('warning');
        }
        return $this;
    }

    public function uploadDuration(float $ms) 
    { 
        $this->data['upload_duration_ms'] = $ms; 
        if (empty($this->data['elapsed_time_ms']) || $ms > $this->data['elapsed_time_ms']) {
            $this->data['elapsed_time_ms'] = $ms;
        }
        return $this; 
    }

    protected static array $firedEvents = [];

    public function save()
    {
        try {
            // Final check on identity if it was missing during init
            if (empty($this->data['portal_user_id'])) {
                $this->resolveIdentity();
            }

            // Sync elapsed time one last time if possible
            if (app()->bound('learner_request_start_time')) {
                $this->data['elapsed_time_ms'] = (microtime(true) - app('learner_request_start_time')) * 1000;
            }

            // --- ANTI-DUPLICATE PROTECTION ---
            if ($this->isDuplicate()) {
                return null;
            }

            $log = LearnerDiagnosticLog::create($this->data);
            
            // Track this event for in-request deduplication
            $this->trackEvent();

            return $log;
        } catch (Throwable $e) {
            Log::error('LearnerLogger failed to save', ['error' => $e->getMessage(), 'payload' => array_keys($this->data)]);
            return null;
        }
    }

    protected function isDuplicate(): bool
    {
        $fingerprint = $this->getFingerprint();
        
        // 1. In-request check (Static memory)
        if (in_array($fingerprint, self::$firedEvents)) {
            return true;
        }

        // 2. Cross-request check (Database, recent window)
        // Only check for meaningful events that shouldn't repeating too fast (page views, access denied, etc.)
        // We allow rapid successions for things like 'upload.progress' if we ever add it.
        $ignoredDedupeCategories = ['upload']; 
        if (in_array($this->data['event_category'], $ignoredDedupeCategories)) {
            return false;
        }

        $isRecent = LearnerDiagnosticLog::where($this->getDedupeQueryCriteria())
            ->where('created_at', '>=', now()->subSeconds(3))
            ->exists();

        return $isRecent;
    }

    protected function trackEvent()
    {
        self::$firedEvents[] = $this->getFingerprint();
    }

    protected function getFingerprint(): string
    {
        return implode(':', [
            $this->data['correlation_id'] ?? 'none',
            $this->data['event_type'],
            $this->data['learner_id'] ?? 'guest',
            $this->data['course_id'] ?? '',
            $this->data['module_id'] ?? '',
            $this->data['lesson_id'] ?? '',
            $this->data['assignment_id'] ?? '',
        ]);
    }

    protected function getDedupeQueryCriteria(): array
    {
        return array_filter([
            'event_type' => $this->data['event_type'],
            'learner_id' => $this->data['learner_id'] ?? null,
            'course_id' => $this->data['course_id'] ?? null,
            'module_id' => $this->data['module_id'] ?? null,
            'lesson_id' => $this->data['lesson_id'] ?? null,
            'assignment_id' => $this->data['assignment_id'] ?? null,
            'url' => $this->data['url'] ?? null,
        ]);
    }
}
