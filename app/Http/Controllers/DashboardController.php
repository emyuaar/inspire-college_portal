<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Crm\Enrolment;
use App\Models\Crm\LearnerOnboardingStatus;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isOrganization()) {
            return redirect()->route('partner.dashboard');
        }

        return redirect()->route('portal.learner.dashboard');
    }

    public function learner(\App\Services\EnrolmentAccessService $accessService)
    {
        $user = Auth::user();

        $enrolments = Enrolment::with(['course', 'status', 'latestOrder'])
            ->where('learner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($e) use ($accessService, $user) {
                $e->access_state = $accessService->getAccessState($e, $user);
                return $e;
            });

        $onboarding = LearnerOnboardingStatus::where('learner_id', $user->id)->first();

        // if any enrolment denied
        $isDenied = $enrolments->contains(fn($e) => (int) $e->status_id === 3);

        $personalCompleted = (bool) ($onboarding->personal_info_completed ?? false);
        $rplCompleted = (bool) ($onboarding->rpl_info_completed ?? false);
        $disabilityCompleted = (bool) ($onboarding->disability_info_completed ?? false);

        $onboardingCompleted = $personalCompleted && $rplCompleted && $disabilityCompleted;

        return view('dashboard.learner', [
            'user' => $user,
            'organization' => $user->isOrgLearner() ? $user->organization : null,
            'enrolments' => $enrolments,
            'onboarding' => $onboarding,
            'onboardingCompleted' => $onboardingCompleted,
            'personalCompleted' => $personalCompleted,
            'rplCompleted' => $rplCompleted,
            'disabilityCompleted' => $disabilityCompleted,
            'isDenied' => $isDenied,
        ]);
    }

    public function organization()
    {
        $user = Auth::user();

        // organization learners
        $learners = $user->learners()
            ->where('id', '!=', $user->id)
            ->get();

        return view('dashboard.organization', [
            'user' => $user,
            'learners' => $learners,
        ]);
    }

    public function allCourses(\App\Services\EnrolmentAccessService $accessService)
    {
        $user = Auth::user();

        $enrolments = Enrolment::with(['course.category', 'status', 'latestOrder'])
            ->where('learner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($e) use ($accessService, $user) {
                $e->access_state = $accessService->getAccessState($e, $user);
                return $e;
            });

        return view('learner.courses.all-courses', compact('user', 'enrolments'));
    }
}
