<?php

namespace App\Http\Controllers;

use App\Models\LearnerDiagnosticLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LearnerDiagnosticController extends Controller
{
    public function logSecurity(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['status' => 'unauthenticated'], 401);
            }

            $validated = $request->validate([
                'event' => 'nullable|string|max:100',
                'course_id' => 'nullable|integer',
                'lesson_id' => 'nullable|integer',
            ]);

            $event = $validated['event'] ?? 'unknown_security_event';
            $courseId = $validated['course_id'] ?? null;
            $lessonId = $validated['lesson_id'] ?? null;

            $cacheKey = sprintf('security_event:%s:%s:%s:%s', $user->id, $event, $courseId ?? 0, $lessonId ?? 0);
            if (Cache::has($cacheKey)) {
                return response()->json(['status' => 'throttled']);
            }

            Cache::put($cacheKey, true, 60);

            LearnerDiagnosticLog::create([
                'portal_user_id' => $user->id,
                'learner_id' => $user->id,
                'learner_name' => $user->name,
                'learner_email' => $user->email,
                'course_id' => $courseId,
                'lesson_id' => $lessonId,
                'event_type' => 'security.' . $event,
                'event_category' => 'security',
                'action_status' => 'blocked',
                'severity' => 'warning',
                'message_human' => 'Security event detected: ' . $event,
                'url' => $request->fullUrl(),
                'route_name' => optional($request->route())->getName(),
                'http_method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'additional_context' => $request->except(['event', 'course_id', 'lesson_id', '_token']),
            ]);

            return response()->json(['status' => 'logged']);
        } catch (\Throwable $e) {
            Log::error('Security event log failed', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
