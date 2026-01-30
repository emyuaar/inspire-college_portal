<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website\Course;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursePurchaseController extends Controller
{
    protected $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Show form to add courses (Multi-Select)
     */
    /**
     * Show form to add courses (Multi-Select)
     * ENFORCED: Only Partner Assigned Courses
     */
    public function create($learnerId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        if (!$learner->crm_approved) {
            return back()->with('error', 'Learner is not approved yet.');
        }

        // Fetch Assigned Courses with Plans
        // Eager load website course
        $assigned = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->with(['course.promotions', 'plans'])
            ->get();

        $courses = $assigned->map(function ($assignment) {
            $course = $assignment->course;
            if (!$course)
                return null;

            // Attach Assignment Notes
            $course->assignment_notes = $assignment->notes;

            // Map ID for View Compatibility (View uses course_id)
            $course->course_id = $course->id;

            // Map Pricing from Assignment
            $fullPlan = $assignment->plans->where('plan_type', 'full')->first();
            $course->final_full_price = $fullPlan ? $fullPlan->amount : 'N/A';
            $course->is_promo = false; // Assigned override

            $instPlan = $assignment->plans->where('plan_type', 'installment')->first();
            $course->installment_plan = [
                'available' => (bool) $instPlan,
                'deposit' => $instPlan ? $instPlan->deposit : 0,
            ];

            return $course;
        })->filter();

        // Note: The view expects $courses to be objects with pricing?
        // Step 119: $courses = $rawCourses->map(...) -> pricingService->getCoursePricing
        // The view 'partner.courses.create' lists courses. If it needs price display, we should probably construct it.
        // For now, let's assume the create view just lists titles. If it shows Price, we might need to mock it.
        // Let's pass the raw course objects for selection.

        return view('partner.courses.create', compact('learner', 'courses'));
    }

    /**
     * Store selected courses as Enrolments (Pending Plan)
     */
    public function store(Request $request, $learnerId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        if (!$learner->crm_approved) {
            abort(403, 'Learner not approved.');
        }

        $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:mysql_crm.partner_assigned_courses,course_id', // Enforce Assignment!
        ]);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // Status: Pending Plan (User needs to select Full vs Installment)
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-plan']);

            $count = 0;
            foreach ($request->course_ids as $courseId) {
                // Secondary check: verify assignment ownership logic
                $valid = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
                    ->where('course_id', $courseId)
                    ->exists();

                if (!$valid)
                    continue;

                // Prevent duplicates
                $exists = Enrolment::where('learner_id', $learner->id)
                    ->where('course_id', $courseId)
                    ->whereHas('status', function ($q) {
                        $q->whereIn('status', ['active', 'pending-payment', 'pending-plan']);
                    })
                    ->exists();

                if ($exists)
                    continue;

                Enrolment::create([
                    'course_id' => $courseId,
                    'learner_id' => $learner->id,
                    'partner_id' => $partner->id, // Track Partner
                    'status_id' => $status->id,
                ]);
                $count++;
            }

            DB::connection('mysql_crm')->commit();

            if ($count == 0) {
                return redirect()->route('partner.learners.show', $learner->id)
                    ->with('warning', 'No new courses added (Learner already enrolled or invalid course).');
            }

            return redirect()->route('partner.learners.show', $learner->id)
                ->with('success', "$count course(s) added. Please select a payment plan for each.");

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Failed to add courses: ' . $e->getMessage());
        }
    }

    /**
     * Show Selection UI for Payment Plan
     */
    public function choosePlan($enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::with(['course', 'learner', 'status'])
            ->findOrFail($enrolmentId);

        if ($enrolment->learner->org_id !== $partner->id) {
            abort(403, 'Unauthorized access to enrolment.');
        }

        if ($enrolment->status->status !== 'pending-plan') {
            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('warning', 'Plan already selected or enrolment active.');
        }

        // Fetch Assignment Logic
        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $enrolment->course_id)
            ->with('plans')
            ->firstOrFail(); // Must be assigned

        // Construct Pricing Object based on Assignment Plans
        // Base structure
        $pricing = new \stdClass();
        $pricing->course_title = $enrolment->course->title;
        $pricing->assignment_notes = $assignment->notes;

        // Full Plan
        $fullPlan = $assignment->plans->where('plan_type', 'full')->first();
        if ($fullPlan && $fullPlan->status) {
            $pricing->full_price_available = true;
            $pricing->final_full_price = $fullPlan->amount;
        } else {
            $pricing->full_price_available = false;
        }

        // Installment Plan
        $instPlan = $assignment->plans->where('plan_type', 'installment')->first();
        if ($instPlan && $instPlan->status) {
            $pricing->installment_plan = (object) [
                'available' => true,
                'deposit' => $instPlan->deposit,
                'months' => $instPlan->months,
                'monthly_amount' => $instPlan->monthly_amount,
                'total_payable' => $instPlan->deposit + ($instPlan->months * $instPlan->monthly_amount)
            ];
        } else {
            $pricing->installment_plan = (object) ['available' => false];
        }

        return view('partner.courses.choose_plan', compact('enrolment', 'pricing'));
    }

    /**
     * Update Enrolment with Plan -> Create Order
     */
    public function updatePlan(Request $request, $enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::findOrFail($enrolmentId);

        if ($enrolment->learner->org_id !== $partner->id)
            abort(403);

        $request->validate([
            'plan_type' => 'required|in:full,installment',
        ]);

        // Verify Assignment logic Again
        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $enrolment->course_id)
            ->with('plans')
            ->firstOrFail();

        DB::connection('mysql_crm')->beginTransaction();

        try {
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-payment']);

            $amount = 0;
            $snapshot = [];

            if ($request->plan_type == 'full') {
                $plan = $assignment->plans->where('plan_type', 'full')->first();
                if (!$plan)
                    return back()->with('error', 'Full payment not available.');

                $amount = $plan->amount;
                $snapshot = [
                    'payment_mode' => 'full',
                    'plan_full_amount' => $plan->amount,
                    'plan_title' => 'Full Payment'
                ];
            } else {
                $plan = $assignment->plans->where('plan_type', 'installment')->first();
                if (!$plan)
                    return back()->with('error', 'Installment plan not available.');

                $amount = $plan->deposit; // Order is for Deposit
                $snapshot = [
                    'payment_mode' => 'installment',
                    'plan_deposit_amount' => $plan->deposit,
                    'plan_months' => $plan->months,
                    'plan_monthly_amount' => $plan->monthly_amount,
                    'plan_title' => "Deposit + {$plan->months} Installments"
                ];
            }

            // Create Order
            $order = Order::create(array_merge([
                'learner_id' => $enrolment->learner_id,
                'enrolment_id' => $enrolment->id,
                'amount' => $amount,
                'status_id' => 0, // Pending
            ], $snapshot));

            // Create Future Installments if needed
            if ($request->plan_type == 'installment') {
                $plan = $assignment->plans->where('plan_type', 'installment')->first();
                $startDate = now();

                // Deposit Row (Installment 0)
                \App\Models\Partner\PartnerLearnerInstallment::create([
                    'partner_id' => $partner->id,
                    'learner_id' => $enrolment->learner_id,
                    'enrolment_id' => $enrolment->id,
                    'order_id' => $order->id,
                    'course_id' => $enrolment->course_id,
                    'plan_type' => 'deposit_installments',
                    'total_amount' => $plan->deposit + ($plan->months * $plan->monthly_amount),
                    'deposit_amount' => $plan->deposit,
                    'installment_amount' => $plan->deposit,
                    'installments_count' => $plan->months,
                    'installment_no' => 0,
                    'due_date' => $startDate,
                    'status' => 'pending',
                ]);

                // Future Rows
                for ($i = 1; $i <= $plan->months; $i++) {
                    \App\Models\Partner\PartnerLearnerInstallment::create([
                        'partner_id' => $partner->id,
                        'learner_id' => $enrolment->learner_id,
                        'enrolment_id' => $enrolment->id,
                        'course_id' => $enrolment->course_id,
                        'plan_type' => 'deposit_installments',
                        'total_amount' => $plan->deposit + ($plan->months * $plan->monthly_amount),
                        'deposit_amount' => $plan->deposit,
                        'installment_amount' => $plan->monthly_amount,
                        'installments_count' => $plan->months,
                        'installment_no' => $i,
                        'due_date' => $startDate->copy()->addMonthsNoOverflow($i),
                        'status' => 'pending',
                    ]);
                }
            }

            $enrolment->update(['status_id' => $status->id]);
            DB::connection('mysql_crm')->commit();

            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('success', 'Plan selected. Please proceed to payment.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

}
