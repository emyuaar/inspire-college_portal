<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\Order;
use App\Models\Crm\PartnerAssignedCourse;
use App\Models\Partner\PartnerLearnerInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Partner Overview Dashboard
     */
    public function index()
    {
        $partner = Auth::user();
        $partnerId = $partner->id;

        // Ensure only partners can access
        if (!$partner->isOrganization()) {
            abort(403, 'Unauthorized. Partners only.');
        }

        // 1. Learner Metrics
        $totalLearners = User::myLearners($partnerId)->count();
        $activeLearners = User::myLearners($partnerId)->where('status_id', 2)->count();
        $pendingApprovals = User::myLearners($partnerId)->where('crm_approved', false)->count();

        // 2. Financial Metrics (Total Success Orders via Partner)
        $learnerIds = User::myLearners($partnerId)->pluck('id');

        $totalRevenue = Order::whereIn('learner_id', $learnerIds)
            ->where('status_id', 1) // Paid/Success in this portal logic
            ->sum('amount');

        // 3. Courses Metric
        $assignedCoursesCount = PartnerAssignedCourse::where('partner_id', $partnerId)->count();

        // 4. Pending Installments (Directly in CRM table)
        $pendingInstallmentsCount = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)
          ->where('status', 'pending')
          ->where('due_date', '<', now()->addDays(7)) // Due within next 7 days
          ->count();
        
        // 5. Pending Plan Decisions & Unsettled Enrolments (Status IDs: 1 (Pending), 5 (Pending Plan), 6 (Pending Payment))
        $pendingPlansCount = Enrolment::whereIn('learner_id', $learnerIds)
            ->whereIn('status_id', [1, 5, 6])
            ->count();

        // 5. Recent Learners
        $recentLearners = User::myLearners($partnerId)
            ->with(['enrolments', 'enrolments.course'])
            ->latest()
            ->take(5)
            ->get();

        // 6. Recent Orders
        $recentOrders = Order::whereIn('learner_id', $learnerIds)
            ->with(['enrolment.course', 'learner'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('partner.dashboard', compact(
            'partner',
            'totalLearners',
            'activeLearners',
            'pendingApprovals',
            'totalRevenue',
            'assignedCoursesCount',
            'pendingInstallmentsCount',
            'pendingPlansCount',
            'recentLearners',
            'recentOrders'
        ));
    }
}
