<?php

namespace App\Http\Controllers;

use App\Services\LearnerLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnerDiagnosticController extends Controller
{
    /**
     * Store a diagnostic log from the frontend.
     */
    public function store(Request $request)
    {
        try {
            // Minimal validation for core fields, allow others to be captured in context or specific columns
            $data = $request->validate([
                'event_type' => 'required|string|max:255',
                'event_category' => 'required|string|max:50',
                'severity' => 'nullable|string|in:info,warning,error,critical',
                'action_status' => 'nullable|string|in:started,success,failed,warning,blocked',
            ], [
                // Custom error messages if needed
            ]);

            // Create logger - it will auto-populate backend context (IP, UserAgent, etc.)
            $logger = LearnerLogger::log($request->event_type, $request->event_category)
                ->severity($request->severity ?? 'info')
                ->status($request->action_status ?? 'success');

            // Explicitly map common fields from frontend payload to columns if they exist
            if ($request->has('course_id')) $logger->courseId($request->course_id);
            if ($request->has('module_id')) $logger->moduleId($request->module_id);
            if ($request->has('lesson_id')) $logger->lessonId($request->lesson_id);
            if ($request->has('assignment_id')) $logger->assignmentId($request->assignment_id);
            
            if ($request->has('message_human')) $logger->humanMessage($request->message_human);
            if ($request->has('message_technical')) $logger->technicalMessage($request->message_technical);
            
            if ($request->has('response_status_code')) $logger->statusCode($request->response_status_code);
            
            if ($request->has('file_name')) {
                $logger->file(
                    $request->file_name, 
                    $request->mime_type, 
                    $request->file_size
                );
            }

            // Capture everything else as context
            $internalKeys = ['event_type', 'event_category', 'severity', 'action_status', '_token'];
            $extra = $request->except(array_merge($internalKeys, [
                'course_id', 'module_id', 'lesson_id', 'assignment_id', 
                'message_human', 'message_technical', 'response_status_code',
                'file_name', 'mime_type', 'file_size'
            ]));

            if (!empty($extra)) {
                $logger->context($extra);
            }

            $logger->save();

            return response()->json(['status' => 'logged', 'correlation_id' => $logger->getCorrelationId()]);
        } catch (\Throwable $e) {
            // Failsafe - don't let logging break the user experience
            Log::error('Frontend diagnostic log ingestion failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
