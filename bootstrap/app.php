<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
        $middleware->alias([
            'installment.access' => \App\Http\Middleware\CheckInstallmentAccess::class,
            'check.status' => \App\Http\Middleware\CheckUserStatus::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\DiagnosticContextMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckUserStatus::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            if (request()->route() && (request()->segment(1) === 'learner' || (auth()->check() && auth()->user()->isLearner()))) {
                $logger = \App\Services\LearnerLogger::logException($e, 'learner.backend.exception', 'error');
                
                // Enrich with request inputs if safe (excluding sensitive fields)
                $inputs = request()->except(['password', 'password_confirmation', '_token', 'credit_card']);
                if (!empty($inputs)) {
                    $logger->context(['request_payload' => $inputs]);
                }
                
                $logger->save();
            }
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // Also log csrf mismatch for learner
            if (request()->route() && (request()->segment(1) === 'learner' || app('auth')->check())) {
                \App\Services\LearnerLogger::log('learner.auth.session_expired', 'error')
                    ->severity('warning')
                    ->status('blocked')
                    ->humanMessage('CSRF Token Mismatch - Session Expired')
                    ->technicalMessage($e->getMessage())
                    ->save();
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Your session has expired. Please refresh the page.'] , 419);
            }
            return redirect()->guest(route('portal.login'))->with('info', 'Your session has expired due to inactivity. Please sign in again to continue.');
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Your session has expired or you are unauthenticated. Please refresh the page.'] , 401);
            }
            return redirect()->guest(route('portal.login'))->with('info', 'Your session has expired for security reasons. Please log in again to continue.');
        });
    })->create();
