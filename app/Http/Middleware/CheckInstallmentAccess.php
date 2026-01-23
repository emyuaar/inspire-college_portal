<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Crm\Enrolment;

class CheckInstallmentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Identify Enrolment from Route Parameter
        // Routes often look like /learner/course/{enrolment} or /learner/lessons/{lesson}
        // We need to resolve the enrolment.

        $enrolment = $request->route('enrolment');

        // If route has 'lesson', we might need to resolve enrolment via lesson->module->course->enrolment
        // But for "Continuing Learning", the main entry is usually /learner/course/{id}
        // Let's protect that first.

        if (!$enrolment && $request->route('lesson')) {
            $lesson = $request->route('lesson');
            // Assuming lesson belongs to course, we need to find the User's enrolment for that course.
            // This is expensive if not eager loaded.
            // For now, let's focus on the main "Course Access" route which uses {enrolment}.
            // If $enrolment is not found or is just an ID string, resolve it.
        }

        if ($enrolment && (is_string($enrolment) || is_numeric($enrolment))) {
            $enrolment = Enrolment::where('id', '=', $enrolment)->first();
        }

        if ($enrolment instanceof Enrolment) {
            // Check Access Status
            $status = $enrolment->installment_access_status;

            if (!$status->allowed) {
                return redirect()->route('portal.learner.dashboard')
                    ->with('error', $status->reason ?? 'Payment overdue – please pay installment first.');
            }
        }

        return $next($request);
    }
}
