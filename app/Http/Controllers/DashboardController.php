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
            return redirect()->route('portal.organization.dashboard');
        }

        return redirect()->route('portal.learner.dashboard');
    }

    public function learner()
    {
        $user = Auth::user();

        $enrolments = Enrolment::with('course')
            ->where('learner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $onboarding = LearnerOnboardingStatus::where('learner_id', $user->id)->first();

        // if any enrolment denied
        $isDenied = $enrolments->contains(fn($e) => (int)$e->status_id === 3);

        $personalCompleted   = (bool) ($onboarding->personal_info_completed   ?? false);
        $rplCompleted        = (bool) ($onboarding->rpl_info_completed        ?? false);
        $disabilityCompleted = (bool) ($onboarding->disability_info_completed ?? false);

        $onboardingCompleted = $personalCompleted && $rplCompleted && $disabilityCompleted;

        return view('dashboard.learner', [
            'user'                => $user,
            'organization'        => $user->isOrgLearner() ? $user->organization : null,
            'enrolments'          => $enrolments,
            'onboarding'          => $onboarding,
            'onboardingCompleted' => $onboardingCompleted,
            'personalCompleted'   => $personalCompleted,
            'rplCompleted'        => $rplCompleted,
            'disabilityCompleted' => $disabilityCompleted,
            'isDenied'            => $isDenied,
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
            'user'     => $user,
            'learners' => $learners,
        ]);
    }

    public function allCourses()
    {
        $user = Auth::user();

        $enrolments = Enrolment::with(['course.category'])
            ->where('learner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('learner.courses.all-courses', compact('user', 'enrolments'));
    }
}
