<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website\Course;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Models\Crm\Order;
use App\Models\Crm\OrderDetail;
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
     * List all Enrolments awaiting a Plan Selection
     */
    public function pendingPlans()
    {
        $partner = Auth::user();
        $learnerIds = User::myLearners($partner->id)->pluck('id');
        
        $enrolments = Enrolment::with(['course', 'learner', 'status'])
            ->whereIn('learner_id', $learnerIds)
            ->whereIn('status_id', [1, 5]) // Removed status 6 (Pending Payment)
            ->latest()
            ->get();

        return view('partner.enrolments.pending_plans', compact('enrolments'));
    }

    /**
     * Show form to add courses (Multi-Select)
     */
    public function index(Request $request)
    {
        $partner = Auth::user();
        $search = $request->get('search');

        $query = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('status', 'active')
            ->with(['course.category']);

        if ($search) {
            $query->whereHas('course', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $assigned = $query->get();

        $courses = $assigned->map(function ($assignment) {
            $course = $assignment->course;
            if (!$course) return null;

            $course->assignment_notes = $assignment->notes;
            
            // Calculate Discounted Price
            $basePrice = (float) $course->regular_price;
            if ($assignment->discount_type == 'percentage') {
                $discount = $basePrice * ($assignment->discount_value / 100);
            } else {
                $discount = $assignment->discount_value;
            }
            $finalFee = max(0, $basePrice - $discount);

            // Populate View-Expected Arrays
            $course->full_plan = [
                'available' => (bool) $assignment->allow_full_payment,
                'amount' => $finalFee
            ];

            // For display purposes, we show either the custom installment or the 3-month automatic one
            $instAvailable = false;
            $instDeposit = 0;
            $instMonths = 0;
            $instMonthly = 0;

            if ($assignment->allow_installments) {
                $customPlan = $assignment->plans()
                    ->where('plan_type', 'installment')
                    ->where('status', 1)
                    ->first();
                
                if ($customPlan) {
                    $instAvailable = true;
                    $instDeposit = $customPlan->deposit;
                    $instMonths = $customPlan->months;
                    $instMonthly = $customPlan->monthly_amount;
                }
            } elseif ($assignment->allow_three_months) {
                // Fallback to showing the 3-month split as "Installments" in the card
                $instAvailable = true;
                $instMonthly = round($finalFee / 3, 2);
                $instDeposit = $instMonthly;
                $instMonths = 2; // +1 = 3
            }

            $course->installment_plan = [
                'available' => $instAvailable,
                'deposit' => $instDeposit,
                'months' => $instMonths,
                'monthly_amount' => $instMonthly
            ];

            $course->final_fee = $finalFee;
            return $course;
        })->filter();

        return view('partner.courses.index', compact('courses', 'search'));
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

        $assigned = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('status', 'active')
            ->with(['course.qualification', 'plans'])
            ->get();

        $courses = $assigned->map(function ($assignment) {
            $course = $assignment->course;
            if (!$course) return null;

            // Map ID for View
            $course->course_id = $course->id;
            
            // 1. Data from Website Database
            $qualModel = $course->getRelation('qualification');
            $course->awarding_body = strip_tags($qualModel->short_title ?? $qualModel->title ?? 'N/A');
            
            // Extract Level from title
            $level = 'N/A';
            if (preg_match('/Level\s+(\d+)/i', $course->title, $matches)) {
                $level = 'Level ' . $matches[1];
            }
            $course->level_name = trim(strip_tags($level));
            
            // 2. Base Price from Website Database (Primary Source)
            $course->base_price = (float) ($course->regular_price ?? 0);
            
            if ($course->base_price <= 0) {
                $course->price_status = 'No website pricing configured';
            } else {
                $course->price_status = null;
            }

            // 3. Discount & Partner Price from CRM
            $dValue = (float) ($assignment->discount_value ?? 0);
            if ($assignment->discount_type == 'percentage') {
                $discountAbs = $course->base_price * ($dValue / 100);
                $course->discount_label = $dValue . '% off';
            } else {
                $discountAbs = $dValue;
                $course->discount_label = $dValue > 0 ? '£' . $dValue . ' off' : '';
            }
            
            $course->final_full_price = max(0, $course->base_price - $discountAbs);
            
            // 4. Payment Modes from CRM
            $course->allow_full = (bool) $assignment->allow_full_payment;
            $course->allow_three = (bool) $assignment->allow_three_months;
            $course->allow_inst = (bool) $assignment->allow_installments;

            // 5. Installment Plan check from CRM (Source of truth for schedule)
            $instPlan = $assignment->plans->where('plan_type', 'installment')->where('status', 1)->first();
            $course->has_plan = (bool) $instPlan;
            
            if ($instPlan) {
                $course->installment_deposit = (float) $instPlan->deposit;
                $course->installment_months = (int) $instPlan->months;
                $course->installment_monthly_amount = (float) $instPlan->monthly_amount;
            }

            return $course;
        })->filter();

        return view('partner.courses.create', compact('learner', 'courses'));
    }

    /**
     * Store selected course (Step 2 Bridge)
     */
    public function store(Request $request, $learnerId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.0' => 'required|integer',
        ]);

        $courseId = $request->course_ids[0];

        // Ensure course is assigned
        $exists = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$exists) {
            return back()->with('error', 'Selected course is not assigned to your account.');
        }

        // Check for existing active/pending enrollment
        $existing = Enrolment::where('learner_id', $learner->id)
            ->where('course_id', $courseId)
            ->whereHas('status', function ($q) {
                $q->whereIn('status', ['active', 'pending-payment', 'pending-plan']);
            })
            ->exists();

        if ($existing) {
            return redirect()->route('partner.learners.show', $learner->id)
                ->with('warning', 'Learner is already enrolled in this course.');
        }

        // Step 2 -> Step 3: Redirect to Plan Selection
        return redirect()->route('partner.enrolments.choose_plan_new', [$learner->id, $courseId]);
    }

    /**
     * Step 3: Choose Plan (Before Enrolment)
     */
    public function choosePlanForCourse($learnerId, $courseId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);
        $course = \App\Models\Website\Course::findOrFail($courseId);

        // Calculate Pricing & Plans (Reusing existing logic or abstracting)
        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->firstOrFail();

        $basePrice = (float) $course->regular_price;
        if ($assignment->discount_type == 'percentage') {
            $discount = $basePrice * ($assignment->discount_value / 100);
        } else {
            $discount = $assignment->discount_value;
        }
        $finalFee = max(0, $basePrice - $discount);

        $plans = $this->getAvailablePlans($assignment, $finalFee);

        return view('partner.courses.choose_plan', [
            'learner' => $learner,
            'course' => $course,
            'plans' => $plans,
            'finalFee' => $finalFee,
            'isNewEnrolment' => true
        ]);
    }

    /**
     * Step 3: Create Enrolment + Order + Schedule
     */
    public function storeEnrolmentWithPlan(Request $request, $learnerId, $courseId)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);
        $course = \App\Models\Website\Course::findOrFail($courseId);

        $request->validate(['plan_type' => 'required|string']);

        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->firstOrFail();

        $basePrice = (float) $course->regular_price;
        $discount = ($assignment->discount_type == 'percentage') ? ($basePrice * ($assignment->discount_value / 100)) : $assignment->discount_value;
        $finalFee = max(0, $basePrice - $discount);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // 1. Create Enrolment (Status: Pending Payment)
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-payment']);
            $enrolment = Enrolment::create([
                'course_id' => $courseId,
                'learner_id' => $learner->id,
                'partner_id' => $partner->id,
                'status_id' => $status->id,
            ]);

            // 2. Create Order & Installments (Reusing updatePlan logic)
            // I'll extract this to a helper or just duplicate for now to be safe with existing types
            $this->processOrderAndSchedule($enrolment, $request->plan_type, $assignment, $finalFee);

            DB::connection('mysql_crm')->commit();

            return redirect()->route('partner.learners.show', $learner->id)
                ->with('success', 'Course enrollment and payment plan created. Please submit payment proof.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function getAvailablePlans($assignment, $finalFee)
    {
        $plans = [];
        if ($assignment->allow_full_payment) {
            $plans['full'] = ['title' => 'Full Payment', 'description' => 'Pay the entire fee upfront.', 'amount' => $finalFee];
        }
        if ($assignment->allow_three_months) {
            $monthly = round($finalFee / 3, 2);
            $plans['three_months'] = [
                'title' => '3 Months Distributed',
                'description' => 'Split into 3 equal monthly payments.',
                'amount' => $monthly,
                'installments' => [
                    ['no' => 0, 'amount' => $monthly, 'due' => 'Today'],
                    ['no' => 1, 'amount' => $monthly, 'due' => 'In 1 Month'],
                    ['no' => 2, 'amount' => $monthly, 'due' => 'In 2 Months'],
                ]
            ];
        }
        if ($assignment->allow_installments) {
            $customPlans = $assignment->plans()
                ->where('plan_type', 'installment')
                ->where('status', 1)
                ->get();

            if ($customPlans->count() > 0) {
                foreach ($customPlans as $index => $customPlan) {
                    $plans['installment_' . $customPlan->id] = [
                        'title' => 'Installment Plan ' . ($customPlans->count() > 1 ? ($index + 1) : ''),
                        'description' => "Deposit of £{$customPlan->deposit} + {$customPlan->months} monthly installments.",
                        'amount' => $customPlan->deposit,
                        'plan_id' => $customPlan->id,
                        'details' => $customPlan
                    ];
                }
            } else {
                $plans['installment_unavailable'] = true;
            }
        }
        return $plans;
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

        // Guard: Prevent re-selection if a plan is already locked in
        $hasExistingPlan = $enrolment->orders()->exists() || \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $enrolment->id)->exists();
        if ($hasExistingPlan) {
            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('warning', 'A payment plan has already been selected for this enrolment.');
        }

        // Fetch Assignment Logic
        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $enrolment->course_id)
            ->where('status', 'active')
            ->firstOrFail();

        // Calculate Final Base Price after Discount
        $basePrice = (float) $enrolment->course->regular_price;
        if ($assignment->discount_type == 'percentage') {
            $discount = $basePrice * ($assignment->discount_value / 100);
        } else {
            $discount = $assignment->discount_value;
        }
        $finalFee = max(0, $basePrice - $discount);

        $plans = [];

        // 1. Full Payment
        if ($assignment->allow_full_payment) {
            $plans['full'] = [
                'title' => 'Full Payment',
                'description' => 'Pay the entire fee upfront.',
                'amount' => $finalFee,
            ];
        }

        // 2. 3 Months Distributed
        if ($assignment->allow_three_months) {
            $monthly = round($finalFee / 3, 2);
            $plans['three_months'] = [
                'title' => '3 Months Distributed',
                'description' => 'Split into 3 equal monthly payments.',
                'amount' => $monthly, // Amount for first payment (Deposit)
                'installments' => [
                    ['no' => 0, 'amount' => $monthly, 'due' => 'Today'],
                    ['no' => 1, 'amount' => $monthly, 'due' => 'In 1 Month'],
                    ['no' => 2, 'amount' => $monthly, 'due' => 'In 2 Months'],
                ]
            ];
        }

        // 3. Installments (From Plan Management)
        if ($assignment->allow_installments) {
            $customPlans = $assignment->plans()
                ->where('plan_type', 'installment')
                ->where('status', 1)
                ->get();

            if ($customPlans->count() > 0) {
                foreach ($customPlans as $index => $customPlan) {
                    $plans['installment_' . $customPlan->id] = [
                        'title' => 'Installment Plan ' . ($customPlans->count() > 1 ? ($index + 1) : ''),
                        'description' => "Deposit of £{$customPlan->deposit} + {$customPlan->months} monthly installments.",
                        'amount' => $customPlan->deposit,
                        'plan_id' => $customPlan->id,
                        'details' => $customPlan
                    ];
                }
            } else {
                $plans['installment_unavailable'] = true;
            }
        }

        return view('partner.courses.choose_plan', [
            'enrolment' => $enrolment,
            'plans' => $plans,
            'finalFee' => $finalFee,
            'isNewEnrolment' => false
        ]);
    }

    /**
     * Update Enrolment with Plan -> Create Order (Step 3 - Legacy/Review)
     */
    public function updatePlan(Request $request, $enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::findOrFail($enrolmentId);

        if ($enrolment->learner->org_id !== $partner->id) abort(403);

        $request->validate([
            'plan_type' => 'required|string',
        ]);

        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $enrolment->course_id)
            ->where('status', 'active')
            ->firstOrFail();

        // Calculate Final Base Price
        $basePrice = (float) $enrolment->course->regular_price;
        $discount = ($assignment->discount_type == 'percentage') ? ($basePrice * ($assignment->discount_value / 100)) : $assignment->discount_value;
        $finalFee = max(0, $basePrice - $discount);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            $this->processOrderAndSchedule($enrolment, $request->plan_type, $assignment, $finalFee);
            
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-payment']);
            $enrolment->update(['status_id' => $status->id]);

            DB::connection('mysql_crm')->commit();

            return redirect()->route('partner.learners.show', $enrolment->learner_id)
                ->with('success', 'Payment plan selected successfully.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function processOrderAndSchedule($enrolment, $planType, $assignment, $finalFee)
    {
        $partner = Auth::user();
        
        // Cleanup existing (if any)
        Order::where('enrolment_id', $enrolment->id)->where('status_id', 3)->forceDelete();
        \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $enrolment->id)->delete();

        $paymentMode = '';
        $planDepositAmount = 0;
        $planMonths = 0;
        $planMonthlyAmount = 0;
        $planTitle = '';

        if ($planType == 'full') {
            if (!$assignment->allow_full_payment) throw new \Exception("Full payment not allowed.");
            $paymentMode = 'full';
            $planTitle = 'Full Payment';
            $planDepositAmount = $finalFee;
            $planMonths = 0;
            $planMonthlyAmount = 0;
        } 
        elseif ($planType == 'three_months') {
            if (!$assignment->allow_three_months) throw new \Exception("3 Months split not allowed.");
            $paymentMode = 'installment';
            $planTitle = '3 Months Distributed';
            $split = round($finalFee / 3, 2);
            $planDepositAmount = $split;
            $planMonths = 2; 
            $planMonthlyAmount = $split;
        }
        else {
            if (!$assignment->allow_installments) throw new \Exception("Installments not allowed.");
            
            // Extract ID if plan_type is installment_{id}
            $planId = str_replace('installment_', '', $planType);
            $plan = \App\Models\Crm\PartnerCoursePaymentPlan::where('pac_id', $assignment->id)
                ->where('id', $planId)
                ->where('status', 1)
                ->firstOrFail();
            
            $paymentMode = 'installment';
            $planTitle = "Installment Plan";
            $planDepositAmount = $plan->deposit;
            $planMonths = $plan->months;
            $planMonthlyAmount = $plan->monthly_amount;
            $finalFee = $plan->amount; // Use the total amount from the plan
        }

        $order = Order::create([
            'learner_id' => $enrolment->learner_id,
            'enrolment_id' => $enrolment->id,
            'amount' => $finalFee,
            'plan_full_amount' => $finalFee,
            'status_id' => 3, // Pending
            'payment_mode' => $paymentMode,
            'plan_deposit_amount' => $planDepositAmount,
            'plan_months' => $planMonths,
            'plan_monthly_amount' => $planMonthlyAmount,
            'plan_title' => $planTitle,
            'deposit_grace_until' => now()->addDays(7),
        ]);

        OrderDetail::create(['order_id' => $order->id, 'course_id' => $enrolment->course_id]);

        $startDate = now();

        if ($planType == 'full') {
            $this->createInstallmentRecord($partner->id, $enrolment, $order, 0, $finalFee, $startDate, $finalFee, 'full', $finalFee, 1);
        } 
        elseif ($planType == 'three_months') {
            $split = round($finalFee / 3, 2);
            $this->createInstallmentRecord($partner->id, $enrolment, $order, 0, $split, $startDate, $finalFee, 'three_months', $split, 3);
            for ($i = 1; $i <= 2; $i++) {
                $dueDate = $startDate->copy()->addMonthsNoOverflow($i);
                $this->createInstallmentRecord($partner->id, $enrolment, $order, $i, $split, $dueDate, $finalFee, 'three_months', $split, 3);
                OrderInstallment::create([
                    'order_id' => $order->id,
                    'installment_no' => $i,
                    'amount' => $split,
                    'due_date' => $dueDate,
                    'payment_status' => 'pending',
                    'amount_paid' => 0,
                ]);
            }
        } 
        else {
            $planId = str_replace('installment_', '', $planType);
            $plan = \App\Models\Crm\PartnerCoursePaymentPlan::where('pac_id', $assignment->id)
                ->where('id', $planId)
                ->where('status', 1)
                ->firstOrFail();

            $this->createInstallmentRecord($partner->id, $enrolment, $order, 0, $plan->deposit, $startDate, $plan->amount, 'installment', $plan->deposit, $plan->months + 1);
            for ($i = 1; $i <= $plan->months; $i++) {
                $dueDate = $startDate->copy()->addMonthsNoOverflow($i);
                $this->createInstallmentRecord($partner->id, $enrolment, $order, $i, $plan->monthly_amount, $dueDate, $plan->amount, 'installment', $plan->deposit, $plan->months + 1);
                OrderInstallment::create([
                    'order_id' => $order->id,
                    'installment_no' => $i,
                    'amount' => $plan->monthly_amount,
                    'due_date' => $dueDate,
                    'payment_status' => 'pending',
                    'amount_paid' => 0,
                ]);
            }
        }
    }

    private function createInstallmentRecord($partnerId, $enrolment, $order, $no, $amount, $date, $total, $planType, $deposit = 0, $count = 1)
    {
        return \App\Models\Partner\PartnerLearnerInstallment::create([
            'partner_id' => $partnerId,
            'learner_id' => $enrolment->learner_id,
            'enrolment_id' => $enrolment->id,
            'order_id' => $order->id,
            'course_id' => $enrolment->course_id,
            'plan_type' => $planType,
            'total_amount' => $total,
            'deposit_amount' => $deposit,
            'installment_amount' => $amount,
            'installments_count' => $count,
            'installment_no' => $no,
            'due_date' => $date,
            'status' => 'pending',
        ]);
    }

    /**
     * Handle Payment Proof Upload (Step 4)
     */
    public function submitProof(Request $request, $enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::findOrFail($enrolmentId);

        if ($enrolment->learner->org_id !== $partner->id) abort(403);

        $request->validate([
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::where('enrolment_id', $enrolment->id)->latest()->firstOrFail();

        // Upload Proof
        $path = $request->file('payment_proof')->store('proofs', 'public');

        // Update the Deposit Installment (no 0)
        $deposit = \App\Models\Partner\PartnerLearnerInstallment::where('order_id', $order->id)
            ->where('installment_no', 0)
            ->first();

        if ($deposit) {
            if (in_array($deposit->status, ['awaiting_approval', 'proof_submitted'])) {
                return redirect()->back()->with('error', 'A payment proof for the deposit is already awaiting approval.');
            }

            $deposit->update([
                'status' => 'awaiting_approval',
                'receipt_path' => $path,
                'payment_reference' => $request->payment_reference,
                'notes' => $request->payment_notes,
            ]);
        }

        return redirect()->back()->with('success', 'Payment proof submitted successfully. Admissions will review it shortly.');
    }
}
