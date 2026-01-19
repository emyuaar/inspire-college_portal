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
    public function create($learnerId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        if (!$learner->crm_approved) {
            return back()->with('error', 'Learner is not approved yet.');
        }

        // Fetch Courses and apply Pricing Logic
        $rawCourses = Course::with(['promotions', 'activeCoursePromotion', 'activePromotion'])->orderBy('title', 'asc')->get();

        $courses = $rawCourses->map(function ($course) {
            return (object) $this->pricingService->getCoursePricing($course);
        });

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
            'course_ids.*' => 'integer|exists:mysql_website.courses,id',
        ]);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // Status: Pending Plan (User needs to select Full vs Installment)
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-plan']);

            $count = 0;
            foreach ($request->course_ids as $courseId) {
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
                    ->with('warning', 'No new courses added (Learner already enrolled).');
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
        // Verify ownership via Learner
        $enrolment = Enrolment::with(['course', 'learner', 'status'])
            ->findOrFail($enrolmentId);

        // Security Check: Ensure Partner owns the Learner
        if ($enrolment->learner->org_id !== $partner->id) {
            abort(403, 'Unauthorized access to enrolment.');
        }

        if ($enrolment->status->status !== 'pending-plan') {
            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('warning', 'Plan already selected or enrolment active.');
        }

        $pricing = (object) $this->pricingService->getCoursePricing($enrolment->course);

        return view('partner.courses.choose_plan', compact('enrolment', 'pricing'));
    }

    /**
     * Update Enrolment with Plan -> Create Order
     */
    public function updatePlan(Request $request, $enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::findOrFail($enrolmentId);

        // Ownership check
        if ($enrolment->learner->org_id !== $partner->id) {
            abort(403);
        }

        $request->validate([
            'plan_type' => 'required|in:full,installment',
        ]);

        $pricing = (object) $this->pricingService->getCoursePricing($enrolment->course);
        $installmentPlan = (object) $pricing->installment_plan;

        // If choosing installment but not available, fail
        if ($request->plan_type == 'installment' && !$installmentPlan->available) {
            return back()->with('error', 'Installment plan not available for this course.');
        }

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // New Status: Pending Payment
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-payment']);

            // 1. Create Order
            // Amount depends on plan
            // 1. Create Order
            // Amount depends on plan
            if ($request->plan_type == 'full') {
                $amount = $pricing->final_full_price;
            } else {
                // FIX ISSUE 1: Order amount MUST be the DEPOSIT amount (due now)
                $amount = $installmentPlan->deposit;
            }

            $order = Order::create([
                'learner_id' => $enrolment->learner_id,
                'enrolment_id' => $enrolment->id, // Link Enrolment
                'amount' => $amount,
                'status_id' => null, // Pending (User requested NULL for pending)

                // PLAN SNAPSHOT
                'payment_mode' => $request->plan_type,
                'plan_deposit_amount' => ($request->plan_type == 'installment') ? $installmentPlan->deposit : null,
                'plan_months' => ($request->plan_type == 'installment') ? $installmentPlan->months : null,
                'plan_monthly_amount' => ($request->plan_type == 'installment') ? $installmentPlan->monthly_amount : null,
                'plan_full_amount' => ($request->plan_type == 'full') ? $pricing->final_full_price : null,
                'plan_title' => ($request->plan_type == 'full') ? 'Full Payment' : ("Deposit + " . $installmentPlan->months . " Installments"),
            ]);

            // 2. Create Future Installments (Remaining Months)
            // FIX ISSUE 2: Do NOT store deposit in order_installments.
            // Store ONLY remaining monthly installments.
            if ($request->plan_type == 'installment') {
                $months = $installmentPlan->months;
                $monthlyAmount = $installmentPlan->monthly_amount;

                for ($i = 1; $i <= $months; $i++) {
                    OrderInstallment::create([
                        'order_id' => $order->id,
                        'amount' => $monthlyAmount,
                        'payment_status' => 'pending',
                        // Optional: Calculate provisional due date (e.g., today + $i months)
                        'due_date' => now()->addMonths($i),
                    ]);
                }
            }

            // 3. Update Enrolment Status
            $enrolment->update(['status_id' => $status->id]);

            DB::connection('mysql_crm')->commit();

            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('success', 'Payment plan selected. You can now proceed to payment.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error saving plan: ' . $e->getMessage());
        }
    }
}
