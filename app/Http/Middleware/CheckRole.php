<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('portal.login');
        }

        $user = Auth::user();

        if ($role === 'partner') {
            if (!$user->isOrganization()) {
                // If a learner tries to access partner routes, redirect to learner dashboard
                return redirect()->route('portal.learner.dashboard')
                    ->with('error', 'Unauthorized access to partner portal.');
            }
        }

        if ($role === 'learner') {
            if ($user->isOrganization()) {
                // If a partner tries to access learner routes, redirect to partner dashboard
                return redirect()->route('partner.learners.index')
                    ->with('error', 'Unauthorized access to learner portal.');
            }
        }

        return $next($request);
    }
}
