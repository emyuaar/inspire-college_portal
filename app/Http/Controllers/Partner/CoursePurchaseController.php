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
use App\Models\Crm\EnrolmentPricingSnapshot;
use App\Services\PartnerCoursePricingService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursePurchaseController extends Controller
{
    protected $partnerPricing;

    public function __construct(PartnerCoursePricingService $partnerPricing)
    {
        $this->partnerPricing = $partnerPricing;
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

        $courses = $assigned->map(function ($assignment) use ($partner) {
            $course = $assignment->course;
            if (!$course) return null;

            $course->assignment_notes = $assignment->notes;
            $quote = $this->partnerPricing->quote($course, $assignment, (int) $partner->id);
            $finalFee = $quote['final_course_fee'];

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
            } elseif ($assignment->allow_two_months || $assignment->allow_three_months) {
                $planType = $assignment->allow_two_months ? 'two_months' : 'three_months';
                $automaticPlan = $this->partnerPricing->buildPlan($assignment, $planType, $quote);
                $instAvailable = true;
                $instMonthly = $automaticPlan['installments'][1]['amount'] ?? $automaticPlan['amount_due_now'];
                $instDeposit = $automaticPlan['amount_due_now'];
                $instMonths = $automaticPlan['number_of_installments'] - 1;
            }

            $course->installment_plan = [
                'available' => $instAvailable,
                'deposit' => $instDeposit,
                'months' => $instMonths,
                'monthly_amount' => $instMonthly
            ];

            $course->final_fee = $finalFee;
            $course->partner_quote = $quote;
            $course->allow_two = (bool) $assignment->allow_two_months;
            $course->allow_three = (bool) $assignment->allow_three_months;
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
        
        // Try active user first
        $learner = User::myLearners($partner->id)->find($learnerId);
        
        // If not found, try pending learner
        if (!$learner) {
            $learner = \App\Models\Crm\PartnerLearner::where('partner_id', $partner->id)->findOrFail($learnerId);
            $learner->is_pending_record = true;
        } else {
            $learner->is_pending_record = false;
        }

        // Pending learners are implicitly "approved" enough to add courses
        if (!$learner->is_pending_record && !$learner->crm_approved) {
            return back()->with('error', 'Learner is not approved yet.');
        }

        $assigned = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('status', 'active')
            ->with(['course.qualification', 'plans'])
            ->get();

        $courses = $assigned->map(function ($assignment) use ($partner) {
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
            
            $quote = $this->partnerPricing->quote($course, $assignment, (int) $partner->id);

            // 2. Base Price from Website Database (Primary Source)
            $course->base_price = $quote['original_course_fee'];
            
            if ($course->base_price <= 0) {
                $course->price_status = 'No website pricing configured';
            } else {
                $course->price_status = null;
            }

            // 3. Discount & Partner Price from CRM
            $dValue = (string) ($assignment->discount_value ?? '0');
            if ($assignment->discount_type == 'percentage') {
                $course->discount_label = rtrim(rtrim($dValue, '0'), '.') . '% off';
            } else {
                $course->discount_label = $quote['partner_discount_amount_minor'] > 0 ? '£' . $dValue . ' off' : '';
            }

            $course->discount_amount = $quote['partner_discount_amount'];
            $course->final_full_price = $quote['final_course_fee'];
            $course->has_partner_discount = $quote['partner_discount_amount_minor'] > 0;
            
            // 4. Payment Modes from CRM
            $course->allow_full = (bool) $assignment->allow_full_payment;
            $course->allow_two = (bool) $assignment->allow_two_months;
            $course->allow_three = (bool) $assignment->allow_three_months;
            $course->allow_inst = (bool) $assignment->allow_installments;

            // 5. Installment Plan check from CRM (Source of truth for schedule)
            $instPlan = $assignment->plans->where('plan_type', 'installment')->where('status', 1)->first();
            $course->has_plan = (bool) $instPlan;
            $course->installment_plan = [
                'available' => (bool) ($assignment->allow_two_months || $assignment->allow_three_months || ($assignment->allow_installments && $instPlan)),
            ];
            
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
        
        // Try active user first
        $learner = User::myLearners($partner->id)->find($learnerId);
        
        // If not found, try pending learner
        if (!$learner) {
            $learner = \App\Models\Crm\PartnerLearner::where('partner_id', $partner->id)->findOrFail($learnerId);
        }

        $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.0' => 'required|integer',
        ]);

        $courseId = $request->course_ids[0];

        // Ensure course is assigned
        $exists = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        if (!$exists) {
            return back()->with('error', 'Selected course is not assigned to your account.');
        }

        // Check for existing active/pending enrollment
        $existing = Enrolment::where(function($q) use ($learner) {
                if (isset($learner->personal_email)) { // It's a PartnerLearner record
                    $q->where('partner_learner_id', $learner->id);
                } else {
                    $q->where('learner_id', $learner->id);
                }
            })
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
        
        // Try active user first
        $learner = User::myLearners($partner->id)->find($learnerId);
        
        // If not found, try pending learner
        if (!$learner) {
            $learner = \App\Models\Crm\PartnerLearner::where('partner_id', $partner->id)->findOrFail($learnerId);
        }
        
        $course = \App\Models\Website\Course::findOrFail($courseId);

        // Calculate Pricing & Plans (Reusing existing logic or abstracting)
        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->firstOrFail();

        $quote = $this->partnerPricing->quote($course, $assignment, (int) $partner->id);

        $alreadyEnrolled = Enrolment::where(function ($query) use ($learner) {
                if (isset($learner->personal_email)) {
                    $query->where('partner_learner_id', $learner->id);
                } else {
                    $query->where('learner_id', $learner->id);
                }
            })
            ->where('course_id', $courseId)
            ->where('partner_id', $partner->id)
            ->whereHas('status', fn ($query) => $query->whereIn('status', ['active', 'pending-payment', 'pending-plan']))
            ->exists();

        if ($alreadyEnrolled) {
            return back()->with('error', 'This learner is already enrolled in the selected course.');
        }

        $plans = $this->getAvailablePlans($assignment, $quote);

        return view('partner.courses.choose_plan', [
            'learner' => $learner,
            'course' => $course,
            'plans' => $plans,
            'quote' => $quote,
            'finalFee' => $quote['final_course_fee'],
            'isNewEnrolment' => true
        ]);
    }

    /**
     * Step 3: Create Enrolment + Order + Schedule
     */
    public function storeEnrolmentWithPlan(Request $request, $learnerId, $courseId)
    {
        $partner = Auth::user();
        
        // Try active user first
        $learner = User::myLearners($partner->id)->find($learnerId);
        
        // If not found, try pending learner
        if (!$learner) {
            $learner = \App\Models\Crm\PartnerLearner::where('partner_id', $partner->id)->findOrFail($learnerId);
        }
        
        $course = \App\Models\Website\Course::findOrFail($courseId);

        $request->validate(['plan_type' => 'required|string|max:100']);

        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->firstOrFail();

        $quote = $this->partnerPricing->quote($course, $assignment, (int) $partner->id);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // 1. Create Enrolment (Status: Pending Payment)
            $status = EnrolmentStatus::firstOrCreate(['status' => 'pending-payment']);
            $enrolmentData = [
                'course_id' => $courseId,
                'partner_id' => $partner->id,
                'status_id' => $status->id,
            ];

            if (isset($learner->personal_email)) {
                // It's a PartnerLearner record (pending)
                $enrolmentData['partner_learner_id'] = $learner->id;
                $enrolmentData['learner_id'] = 0; // Or null, depending on DB schema
            } else {
                // It's an active User
                $enrolmentData['learner_id'] = $learner->id;
            }

            $enrolment = Enrolment::create($enrolmentData);

            // 2. Create Order & Installments (Reusing updatePlan logic)
            // I'll extract this to a helper or just duplicate for now to be safe with existing types
            $this->processOrderAndSchedule($enrolment, $request->plan_type, $assignment, $quote);

            DB::connection('mysql_crm')->commit();

            return redirect()->route('partner.learners.show', $learner->id)
                ->with('success', 'Course enrollment and payment plan created. Please submit payment proof.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function getAvailablePlans($assignment, array $quote)
    {
        $plans = [];
        if ($assignment->allow_full_payment) {
            $plans['full'] = $this->viewPlan($this->partnerPricing->buildPlan($assignment, 'full', $quote), 'Pay the entire discounted fee upfront.');
        }
        if ($assignment->allow_two_months) {
            $plans['two_months'] = $this->viewPlan($this->partnerPricing->buildPlan($assignment, 'two_months', $quote), 'Split the discounted fee across 2 monthly payments.');
        }
        if ($assignment->allow_three_months) {
            $plans['three_months'] = $this->viewPlan($this->partnerPricing->buildPlan($assignment, 'three_months', $quote), 'Split the discounted fee across 3 monthly payments.');
        }
        if ($assignment->allow_installments) {
            $customPlans = $assignment->plans()
                ->where('plan_type', 'installment')
                ->where('status', 1)
                ->get();

            if ($customPlans->count() > 0) {
                foreach ($customPlans as $index => $customPlan) {
                    $builtPlan = $this->partnerPricing->buildPlan($assignment, 'installment_' . $customPlan->id, $quote, $customPlan);
                    $plans['installment_' . $customPlan->id] = $this->viewPlan(
                        $builtPlan,
                        'Configured deposit followed by ' . $customPlan->months . ' monthly payments.',
                        $customPlan
                    );
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
        $enrolment = Enrolment::where('partner_id', $partner->id)
            ->with(['course', 'learner', 'status'])
            ->findOrFail($enrolmentId);

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

        $quote = $this->partnerPricing->quote($enrolment->course, $assignment, (int) $partner->id);
        $plans = $this->getAvailablePlans($assignment, $quote);

        return view('partner.courses.choose_plan', [
            'enrolment' => $enrolment,
            'plans' => $plans,
            'quote' => $quote,
            'finalFee' => $quote['final_course_fee'],
            'isNewEnrolment' => false
        ]);
    }

    /**
     * Update Enrolment with Plan -> Create Order (Step 3 - Legacy/Review)
     */
    public function updatePlan(Request $request, $enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = Enrolment::where('partner_id', $partner->id)->findOrFail($enrolmentId);

        $request->validate([
            'plan_type' => 'required|string|max:100',
        ]);

        $assignment = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('course_id', $enrolment->course_id)
            ->where('status', 'active')
            ->firstOrFail();

        $quote = $this->partnerPricing->quote($enrolment->course, $assignment, (int) $partner->id);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            $this->processOrderAndSchedule($enrolment, $request->plan_type, $assignment, $quote);
            
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

    private function viewPlan(array $plan, string $description, $details = null): array
    {
        $viewInstallments = [];
        foreach ($plan['installments'] as $installment) {
            $offset = $installment['offset_months'];
            $viewInstallments[] = $installment + [
                'due' => $offset === 0 ? 'Today' : ($offset === 1 ? 'In 1 Month' : "In {$offset} Months"),
            ];
        }

        return [
            'title' => $plan['title'],
            'description' => $description,
            'amount' => $plan['amount_due_now'],
            'total_amount' => $plan['total_amount'],
            'number_of_installments' => $plan['number_of_installments'],
            'installments' => $viewInstallments,
            'details' => $details,
        ];
    }

    private function processOrderAndSchedule($enrolment, string $planType, $assignment, array $quote): Order
    {
        $partner = Auth::user();
        $customPlan = null;

        if (str_starts_with($planType, 'installment_')) {
            $planId = substr($planType, strlen('installment_'));
            if (!ctype_digit($planId)) {
                throw new DomainException('The selected payment plan is invalid.');
            }

            $customPlan = \App\Models\Crm\PartnerCoursePaymentPlan::where('pac_id', $assignment->id)
                ->where('id', (int) $planId)
                ->where('status', 1)
                ->first();
        }

        $plan = $this->partnerPricing->buildPlan($assignment, $planType, $quote, $customPlan);
        $futureInstallmentAmount = $plan['installments'][1]['amount'] ?? '0.00';

        $order = Order::create([
            'learner_id' => $enrolment->learner_id,
            'partner_learner_id' => $enrolment->partner_learner_id,
            'enrolment_id' => $enrolment->id,
            'amount' => $plan['amount_due_now'],
            'plan_full_amount' => $quote['final_course_fee'],
            'status_id' => 3, // Pending
            'payment_mode' => $plan['payment_mode'],
            'plan_deposit_amount' => $plan['amount_due_now'],
            'plan_months' => $plan['number_of_installments'] - 1,
            'plan_monthly_amount' => $futureInstallmentAmount,
            'plan_title' => $plan['title'],
            'plan_meta' => [
                'source' => 'partner_course_assignment',
                'quote' => $quote,
                'selected_payment_plan' => $plan['type'],
                'number_of_installments' => $plan['number_of_installments'],
                'installment_amounts' => $plan['installment_amounts'],
            ],
            'deposit_grace_until' => now()->addDays(7),
        ]);

        OrderDetail::create(['order_id' => $order->id, 'course_id' => $enrolment->course_id]);

        $startDate = now();
        foreach ($plan['installments'] as $index => $installment) {
            $dueDate = $startDate->copy()->addMonthsNoOverflow($installment['offset_months']);
            $this->createInstallmentRecord(
                $partner->id,
                $enrolment,
                $order,
                $index,
                $installment['amount'],
                $dueDate,
                $quote['final_course_fee'],
                $plan['type'],
                $plan['amount_due_now'],
                $plan['number_of_installments']
            );

            if ($index > 0) {
                OrderInstallment::create([
                    'order_id' => $order->id,
                    'installment_no' => $index,
                    'amount' => $installment['amount'],
                    'due_date' => $dueDate,
                    'payment_status' => 'pending',
                    'amount_paid' => 0,
                ]);
            }
        }

        EnrolmentPricingSnapshot::create([
            'learner_id' => $enrolment->learner_id ?: null,
            'enrolment_id' => $enrolment->id,
            'order_id' => $order->id,
            'course_id' => $enrolment->course_id,
            'regular_fee' => $quote['original_course_fee'],
            'discounted_fee' => $quote['final_course_fee'],
            'discount_amount' => $quote['partner_discount_amount'],
            'discount_percentage' => $quote['partner_discount_type'] === 'percentage' ? $quote['partner_discount_value'] : '0.00',
            'initial_deposit' => $plan['amount_due_now'],
            'installment_months' => $plan['number_of_installments'] - 1,
            'installment_amount' => $futureInstallmentAmount,
            'total_payable' => $quote['final_course_fee'],
            'selected_plan_name' => $plan['title'],
            'selected_plan_type' => $plan['payment_mode'] === 'full' ? 'full_fee' : 'installment',
            'snapshot_json' => [
                'source' => 'partner_dashboard',
                'partner_id' => (int) $partner->id,
                'learner_id' => $enrolment->learner_id ?: null,
                'partner_learner_id' => $enrolment->partner_learner_id ?: null,
                'course_id' => (int) $enrolment->course_id,
                'partner_course_assignment_id' => (int) $assignment->id,
                'original_course_fee' => $quote['original_course_fee'],
                'partner_discount_type' => $quote['partner_discount_type'],
                'partner_discount_value' => $quote['partner_discount_value'],
                'partner_discount_amount' => $quote['partner_discount_amount'],
                'final_course_fee' => $quote['final_course_fee'],
                'selected_payment_plan' => $plan['type'],
                'number_of_installments' => $plan['number_of_installments'],
                'installment_amounts' => $plan['installment_amounts'],
            ],
        ]);

        return $order;
    }

    private function createInstallmentRecord($partnerId, $enrolment, $order, $no, $amount, $date, $total, $planType, $deposit = 0, $count = 1)
    {
        return \App\Models\Partner\PartnerLearnerInstallment::create([
            'partner_id' => $partnerId,
            'learner_id' => $enrolment->learner_id ?: $enrolment->partner_learner_id,
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
        $enrolment = Enrolment::where('partner_id', $partner->id)->findOrFail($enrolmentId);

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

            // Trigger CRM Notification
            try {
                app(\App\Services\CrmNotificationService::class)->notifyAdminsForProofSubmission($deposit);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("CRM_NOTIFICATION_FAILED: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Payment proof submitted successfully. Admissions will review it shortly.');
    }
}
