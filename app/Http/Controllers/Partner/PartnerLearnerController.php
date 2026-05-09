<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Models\Crm\Order;
use App\Models\Crm\OrderDetail;
use App\Models\Crm\OrderInstallment;
use App\Models\Crm\UserDetail as CrmUserDetail;
use App\Models\Crm\PartnerAssignedCourse;
use App\Models\Crm\PartnerInstallmentPlan;
use App\Models\Crm\PartnerLearner;
use App\Models\Crm\PartnerLearnerInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use App\Services\PaymentProcessingService;
use App\Services\PricingService;

class PartnerLearnerController extends Controller
{
    /**
     * Partner Dashboard / List Learners
     */
    public function index()
    {
        $partner = Auth::user();

        // Ensure only partners can access
        if (!$partner->isOrganization()) {
            abort(403, 'Unauthorized. Partners only.');
        }

        $activeLearners = User::myLearners($partner->id)
            ->with(['enrolments', 'enrolments.status'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingLearners = PartnerLearner::where('partner_id', $partner->id)
            ->where('activation_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('partner.learners.index', compact('partner', 'activeLearners', 'pendingLearners'));
    }

    /**
     * Show Learner Details and Courses
     */
    public function show(Request $request, $id, PricingService $pricingService, PaymentProcessingService $paymentService)
    {
        $partner = Auth::user();
        $isPending = false;
        $learner = null;

        if (str_starts_with($id, 'pending-')) {
            $isPending = true;
            $id = str_replace('pending-', '', $id);
            $learner = PartnerLearner::findOrFail($id);
        } else {
            // Try active user first
            $learner = User::myLearners(Auth::id())->find($id);
            
            // If not found, check if it's a pending learner ID
            if (!$learner) {
                $learner = PartnerLearner::where('partner_id', Auth::id())->find($id);
                if ($learner) {
                    $isPending = true;
                }
            }
        }

        if (!$learner) {
            abort(404, 'Learner not found.');
        }

        // Check for synchronous payment confirmation (Fix for localhost/missed webhooks)
        if ($request->has('payment') && $request->payment === 'success' && $request->filled('session_id')) {
            try {
                Stripe::setApiKey(env('STRIPE_SECRET'));
                $session = StripeSession::retrieve($request->session_id);

                if ($session->payment_status === 'paid') {
                    // Process payment (idempotent service)
                    $paymentService->processOrderPayment($session, $session->metadata);

                    // Flash specific success only if not already there to avoid dupes or confusion
                    session()->flash('success', 'Payment confirmed! Enrolment is now active.');
                }
            } catch (\Exception $e) {
                // Log error but allow page to load
                \Illuminate\Support\Facades\Log::error("Sync Payment Check Failed: " . $e->getMessage());
            }
        }

        if ($isPending) {
            $enrolments = Enrolment::where('partner_learner_id', $learner->id)
                ->with(['course', 'status'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $enrolments = Enrolment::where('learner_id', $learner->id)
                ->with(['course', 'status'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Fetch Only Assigned Courses for the "Add Course" Modal
        $assignedCourses = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->where('status', 'active') // Only active assignments
            ->with(['course.qualification', 'plans'])
            ->get();

        // Get IDs of currently enrolled courses to filter them out
        $enrolledCourseIds = $enrolments->pluck('course_id')->unique()->toArray();

        $courses = $assignedCourses->map(function ($assignment) use ($enrolledCourseIds) {
            $course = $assignment->course;
            if (!$course) return null;

            // Exclude if already enrolled
            if (in_array($course->id, $enrolledCourseIds)) {
                return null;
            }

            // 1. Map ID for View
            $course->course_id = $course->id;

            // 2. Data from Website Database
            $qual = $course->qualification;
            $course->awarding_body = strip_tags($qual?->short_title ?? $qual?->title ?? 'N/A');

            // Extract Level from title
            $level = 'N/A';
            if (preg_match('/Level\s+(\d+)/i', $course->title, $matches)) {
                $level = 'Level ' . $matches[1];
            }
            $course->level_name = trim(strip_tags($level));

            // 3. Base Price from Website Database (Primary Source)
            $course->base_price = (float) ($course->sale_price ?? 0);
            $course->price_status = ($course->base_price <= 0) ? 'No website pricing configured' : null;

            // 4. Discount & Partner Price from CRM
            $dValue = (float) ($assignment->discount_value ?? 0);
            if ($assignment->discount_type == 'percentage') {
                $discountAbs = $course->base_price * ($dValue / 100);
                $course->discount_label = $dValue . '% off';
            } else {
                $discountAbs = $dValue;
                $course->discount_label = $dValue > 0 ? '£' . $dValue . ' off' : '';
            }

            $course->final_full_price = max(0, $course->base_price - $discountAbs);

            // 5. Payment Modes from CRM
            $course->allow_full = (bool) $assignment->allow_full_payment;
            $course->allow_three = (bool) $assignment->allow_three_months;
            $course->allow_inst = (bool) $assignment->allow_installments;

            // 6. Installment Plan check from CRM
            $instPlan = $assignment->plans ? $assignment->plans->where('plan_type', 'installment')->where('status', 1)->first() : null;
            $course->has_plan = (bool) $instPlan;

            return $course;
        })->filter()->values();

        \Log::error("DEBUG Final available courses count: " . $courses->count());

        // Fetch Partner Installments (Manual Plan) - Correctly linked via Enrolment IDs
        $installments = \App\Models\Partner\PartnerLearnerInstallment::whereIn('enrolment_id', $enrolments->pluck('id'))
            ->with('course') // meaningful info
            ->orderBy('due_date', 'asc')
            ->get();

        return view('partner.learners.show', compact('learner', 'enrolments', 'courses', 'installments', 'isPending'));
    }

    /**
     * Show Create Learner Form (Step 1)
     */
    public function create()
    {
        return view('partner.learners.create');
    }

    /**
     * Store New Learner (Step 1)
     */
    public function store(Request $request)
    {
        $partner = Auth::user();

        // 1. Validation (Learner Details Only)
        $request->validate([
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'sur_name' => 'required|string|max:60',
            'email_address' => 'required|email',
            'contact_number' => 'required|string|max:50',
            'dob' => 'required|date',
            'gender' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'zip_code' => 'required|string|max:30',
        ]);

        DB::beginTransaction();
        DB::connection('mysql_crm')->beginTransaction();

        try {
            // A) Create Partner Learner Record (CRM)
            $partnerLearner = PartnerLearner::create([
                'partner_id' => $partner->id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name ?? '',
                'last_name' => $request->sur_name,
                'personal_email' => $request->email_address,
                'phone' => $request->contact_number,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2 ?? '',
                'city' => $request->city,
                'state' => $request->state ?? '',
                'country' => $request->country,
                'zip_code' => $request->zip_code,
                'payment_status' => 'pending',
                'activation_status' => 'pending',
                'enrolment_status' => 'pending_payment',
                'account_status' => 'pending',
                'created_by_partner_id' => $partner->id,
            ]);

            DB::connection('mysql_crm')->commit();
            DB::commit();

            return redirect()->route('partner.learners.show', $partnerLearner->id)
                ->with('success', "Learner request submitted successfully. The learner account will be activated once the payment is confirmed.");

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    private function getPlanData($type, $total, $partnerId, $courseId)
    {
        if ($type == 'full') {
            return [
                'payment_mode' => 'full',
                'deposit' => $total,
                'months' => 0,
                'monthly' => 0,
                'title' => 'Full Payment'
            ];
        } 
        elseif ($type == 'three_months') {
            $split = round($total / 3, 2);
            return [
                'payment_mode' => 'installment',
                'deposit' => $split,
                'months' => 2,
                'monthly' => $split,
                'title' => '3 Months Distributed'
            ];
        } 
        else {
            $plan = PartnerInstallmentPlan::where('partner_id', $partnerId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->firstOrFail();
            
            return [
                'payment_mode' => 'installment',
                'deposit' => $plan->deposit_amount,
                'months' => $plan->installment_count,
                'monthly' => $plan->installment_amount,
                'title' => $plan->plan_name
            ];
        }
    }

    private function createInstallments($type, $partnerId, $learnerId, $enrolmentId, $order, $total, $planData)
    {
        $startDate = now();

        // Portal Tracking (Deposit/Full always no 0)
        PartnerLearnerInstallment::create([
            'partner_id' => $partnerId,
            'learner_id' => $learnerId,
            'enrolment_id' => $enrolmentId,
            'order_id' => $order->id,
            'course_id' => $order->details->first()->course_id ?? 0,
            'plan_type' => $type,
            'total_amount' => $total,
            'deposit_amount' => $planData['deposit'],
            'installment_amount' => $planData['deposit'],
            'installments_count' => $planData['months'] + 1,
            'installment_no' => 0,
            'due_date' => $startDate,
            'status' => 'pending',
        ]);

        // Remaining Installments
        for ($i = 1; $i <= $planData['months']; $i++) {
            $dueDate = $startDate->copy()->addMonthsNoOverflow($i);
            
            // Portal Tracking
            PartnerLearnerInstallment::create([
                'partner_id' => $partnerId,
                'learner_id' => $learnerId,
                'enrolment_id' => $enrolmentId,
                'order_id' => $order->id,
                'course_id' => $order->details->first()->course_id ?? 0,
                'plan_type' => $type,
                'total_amount' => $total,
                'deposit_amount' => $planData['deposit'],
                'installment_amount' => $planData['monthly'],
                'installments_count' => $planData['months'] + 1,
                'installment_no' => $i,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);

            // CRM Tracking
            OrderInstallment::create([
                'order_id' => $order->id,
                'installment_no' => $i,
                'amount' => $planData['monthly'],
                'due_date' => $dueDate,
                'payment_status' => 'pending',
                'amount_paid' => 0,
            ]);
        }
    }
}
