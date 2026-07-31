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

        // 4. Installment Metrics (Shared logic with InstallmentController)
        $overdueInstallmentsCount = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->startOfDay())
            ->count();

        $dueSoonInstallmentsCount = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)
            ->where('status', '!=', 'paid')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->count();
        
        // 5. Pending Plan Selection (Only those without any plan yet)
        $pendingPlansCount = Enrolment::whereIn('learner_id', $learnerIds)
            ->whereIn('status_id', [1, 5]) // Standard pending/pending-plan statuses
            ->whereDoesntHave('orders')
            ->whereDoesntHave('partnerInstallments')
            ->count();

        // 5. Recent Learners
        $recentLearners = User::myLearners($partnerId)
            ->with(['enrolments', 'enrolments.course'])
            ->latest()
            ->take(5)
            ->get();

        // 6. Recent Transactions (Unifying logic with TransactionController)
        $txInstallments = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)
            ->where('status', 'paid')
            ->where('installment_no', '>', 0) // Deposits covered by Order query below
            ->with(['learner', 'course'])
            ->latest('paid_at')
            ->take(10)
            ->get();

        $txOrders = Order::whereIn('learner_id', $learnerIds)
            ->where('status_id', 1)
            ->with(['learner', 'enrolment.course'])
            ->latest()
            ->take(10)
            ->get();

        $recentTransactions = $txInstallments->map(function($i) {
            return (object)[
                'learner' => $i->learner,
                'course_title' => $i->course->title ?? 'Course',
                'amount' => $i->paid_amount,
                'date' => $i->paid_at ?? $i->updated_at,
                'type' => 'Installment #' . $i->installment_no
            ];
        })->concat($txOrders->map(function($o) {
            return (object)[
                'learner' => $o->learner,
                'course_title' => $o->enrolment->course->title ?? 'Course',
                'amount' => $o->amount,
                'date' => $o->updated_at ?? $o->created_at,
                'type' => ($o->payment_mode === 'full' ? 'Full Payment' : 'Deposit')
            ];
        }))->sortByDesc('date')->take(5);

        return view('partner.dashboard', compact(
            'partner',
            'totalLearners',
            'activeLearners',
            'pendingApprovals',
            'totalRevenue',
            'assignedCoursesCount',
            'overdueInstallmentsCount',
            'dueSoonInstallmentsCount',
            'pendingPlansCount',
            'recentLearners',
            'recentTransactions'
        ));
    }
}
