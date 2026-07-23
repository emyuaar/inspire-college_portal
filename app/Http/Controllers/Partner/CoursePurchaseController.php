<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website\Course;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use App\Models\Crm\PartnerLearner;
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
    public function index()
    {
        $partner = Auth::user();

        // Fetch Assigned Courses with Plans
        $assigned = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->with(['course.promotions', 'plans'])
            ->get();

        $courses = $assigned->map(function ($assignment) {
            $course = $assignment->course;
            if (!$course)
                return null;

            // Attach Assignment Notes
            $course->assignment_notes = $assignment->notes;

            // Map Pricing from Assignment
            $fullPlan = $assignment->plans->where('plan_type', 'full')->first();
            $course->full_plan = [
                'available' => (bool) $fullPlan,
                'amount' => $fullPlan ? $fullPlan->amount : null,
            ];

            $instPlan = $assignment->plans->where('plan_type', 'installment')->first();
            $course->installment_plan = [
                'available' => (bool) $instPlan,
                'deposit' => $instPlan ? $instPlan->deposit : 0,
                'months' => $instPlan ? $instPlan->months : 0,
                'monthly_amount' => $instPlan ? $instPlan->monthly_amount : 0,
            ];

            return $course;
        })->filter();

        return view('partner.courses.index', compact('courses'));
    }

    /**
     * Show form to add courses (Multi-Select)
     * ENFORCED: Only Partner Assigned Courses
     */
    public function create($learnerId)
    {
        $partner = Auth::user();
        [$learner, $isPending] = $this->resolvePartnerLearner($partner->id, $learnerId);

        if (!$isPending && !$learner->crm_approved) {
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

        return view('partner.courses.create', compact('learner', 'courses', 'isPending'));
    }

    /**
     * Store selected courses as Enrolments (Pending Plan)
     */
    public function store(Request $request, $learnerId)
    {
        $partner = Auth::user();
        [$learner, $isPending] = $this->resolvePartnerLearner($partner->id, $learnerId);

        if (!$isPending && !$learner->crm_approved) {
            abort(403, 'Learner not approved.');
        }

        $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => [
                'integer',
                // Custom rule to ensure the course is assigned to THIS partner
                function ($attribute, $value, $fail) use ($partner) {
                    $exists = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
                        ->where('course_id', $value)
                        ->exists();
                    if (!$exists) {
                        $fail("The selected course (ID: $value) is not assigned to your account.");
                    }
                },
            ],
        ]);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // Status: Pending Plan (User needs to select Full vs Installment)
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-plan']);

            $count = 0;
            foreach ($request->course_ids as $courseId) {
                // Prevent duplicates
                $exists = Enrolment::query()
                    ->when($isPending, fn ($query) => $query->where('partner_learner_id', $learner->id))
                    ->when(! $isPending, fn ($query) => $query->where('learner_id', $learner->id))
                    ->where('course_id', $courseId)
                    ->whereHas('status', function ($q) {
                        $q->whereIn('status', ['active', 'pending-payment', 'pending-plan']);
                    })
                    ->exists();

                if ($exists)
                    continue;

                $payload = [
                    'course_id' => $courseId,
                    'partner_id' => $partner->id, // Track Partner
                    'status_id' => $status->id,
                ];

                if ($isPending) {
                    $payload['learner_id'] = 0;
                    $payload['partner_learner_id'] = $learner->id;
                } else {
                    $payload['learner_id'] = $learner->id;
                }

                Enrolment::create($payload);
                $count++;
            }

            DB::connection('mysql_crm')->commit();

            if ($count == 0) {
                return redirect()->route('partner.learners.show', $this->routeLearnerId($learner, $isPending))
                    ->with('warning', 'No new courses added (Learner already enrolled or invalid course).');
            }

            return redirect()->route('partner.learners.show', $this->routeLearnerId($learner, $isPending))
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

        if (! $this->partnerOwnsEnrolment($enrolment, $partner->id)) {
            abort(403, 'Unauthorized access to enrolment.');
        }

        if ($enrolment->status->status !== 'pending-plan') {
            return redirect()->route('partner.learners.show', $this->routeEnrolmentLearnerId($enrolment))
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

        if (! $this->partnerOwnsEnrolment($enrolment, $partner->id))
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
                'learner_id' => $enrolment->learner_id ?: 0,
                'partner_learner_id' => $enrolment->partner_learner_id,
                'enrolment_id' => $enrolment->id,
                'amount' => $amount,
                'status_id' => 0, // Pending
            ], $snapshot));

            // Create Future Installments if needed
            if ($request->plan_type == 'full') {
                \App\Models\Partner\PartnerLearnerInstallment::create([
                    'partner_id' => $partner->id,
                    'learner_id' => $enrolment->learner_id ?: ($enrolment->partner_learner_id ?: 0),
                    'enrolment_id' => $enrolment->id,
                    'order_id' => $order->id,
                    'course_id' => $enrolment->course_id,
                    'plan_type' => 'full',
                    'total_amount' => $amount,
                    'deposit_amount' => $amount,
                    'installment_amount' => $amount,
                    'installments_count' => 1,
                    'installment_no' => 0,
                    'due_date' => now(),
                    'status' => 'pending',
                ]);
            } elseif ($request->plan_type == 'installment') {
                $plan = $assignment->plans->where('plan_type', 'installment')->first();
                $startDate = now();

                // Deposit Row (Installment 0)
                \App\Models\Partner\PartnerLearnerInstallment::create([
                    'partner_id' => $partner->id,
                    'learner_id' => $enrolment->learner_id ?: ($enrolment->partner_learner_id ?: 0),
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
                        'learner_id' => $enrolment->learner_id ?: ($enrolment->partner_learner_id ?: 0),
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

            return redirect()->route('partner.learners.show', $this->routeEnrolmentLearnerId($enrolment))
                ->with('success', 'Plan selected. Please proceed to payment.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function resolvePartnerLearner(int $partnerId, string|int $learnerId): array
    {
        if (str_starts_with((string) $learnerId, 'pending-')) {
            $id = (int) str_replace('pending-', '', (string) $learnerId);
            return [PartnerLearner::where('partner_id', $partnerId)->findOrFail($id), true];
        }

        $learner = User::myLearners($partnerId)->find($learnerId);
        if ($learner) {
            return [$learner, false];
        }

        return [PartnerLearner::where('partner_id', $partnerId)->findOrFail($learnerId), true];
    }

    private function partnerOwnsEnrolment(Enrolment $enrolment, int $partnerId): bool
    {
        if ((int) $enrolment->partner_id === $partnerId) {
            return true;
        }

        if ($enrolment->partner_learner_id) {
            return PartnerLearner::where('partner_id', $partnerId)->where('id', $enrolment->partner_learner_id)->exists();
        }

        return $enrolment->learner && (int) $enrolment->learner->org_id === $partnerId;
    }

    private function routeEnrolmentLearnerId(Enrolment $enrolment): string|int
    {
        if ($enrolment->partner_learner_id) {
            return 'pending-' . $enrolment->partner_learner_id;
        }

        return $enrolment->learner_id;
    }

    private function routeLearnerId($learner, bool $isPending): string|int
    {
        return $isPending ? 'pending-' . $learner->id : $learner->id;
    }

}
