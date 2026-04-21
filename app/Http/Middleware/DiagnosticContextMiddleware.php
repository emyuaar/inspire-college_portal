<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use App\Services\LearnerLogger;

class DiagnosticContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Generate/Ensure Correlation ID exists for the duration of this request
        if (!app()->bound('learner_request_correlation_id')) {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
            app()->instance('learner_request_correlation_id', $correlationId);
        }

        // 2. Start timer for elapsed time tracking
        $startTime = microtime(true);
        app()->instance('learner_request_start_time', $startTime);

        $response = $next($request);

        // 3. Attach correlation ID to response headers for frontend tracking
        $response->headers->set('X-Correlation-ID', app('learner_request_correlation_id'));

        return $response;
    }
}
