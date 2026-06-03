<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Enrolment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $learners = User::myLearners($partner->id)
            ->with(['enrolments', 'enrolments.status']) // Assuming relationships exist or will filter in view
            ->orderBy('created_at', 'desc')
            ->get();

        return view('partner.learners.index', compact('partner', 'learners'));
    }

    /**
     * Show Learner Details and Courses
     */
    public function show(Request $request, $id, PricingService $pricingService, PaymentProcessingService $paymentService)
    {
        $partner = Auth::user();
        $learner = User::myLearners($partner->id)->findOrFail($id);

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

        $enrolments = Enrolment::where('learner_id', $learner->id)
            ->with(['course', 'status'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch Only Assigned Courses for the "Add Course" Modal
        $assignedCourses = \App\Models\Crm\PartnerAssignedCourse::where('partner_id', $partner->id)
            ->with(['course', 'plans'])
            ->get();

        // Get IDs of currently enrolled courses to filter them out
        $enrolledCourseIds = $enrolments->pluck('course_id')->unique();

        $courses = $assignedCourses->map(function ($assignment) use ($enrolledCourseIds) {
            $course = $assignment->course;
            if (!$course)
                return null;

            // Exclude if already enrolled
            if ($enrolledCourseIds->contains($course->id)) {
                return null;
            }

            // Pricing & Plans from Assignment
            $fullPlan = $assignment->plans->where('plan_type', 'full')->where('status', true)->first();
            $instPlan = $assignment->plans->where('plan_type', 'installment')->where('status', true)->first();

            // Construct Data Object for Frontend (matches `add_modal.blade.php` expectations)
            return (object) [
                'course_id' => $course->id,
                'title' => $course->title,
                'is_promo' => false, // Partner assignments have fixed pricing, ignoring standard promos for now
                'final_full_price' => $fullPlan ? number_format($fullPlan->amount, 2) : 'N/A', // Display string
                'full_payment_available' => (bool) $fullPlan,
                'installment_plan' => (object) [
                    'available' => (bool) $instPlan,
                    'deposit' => $instPlan ? $instPlan->deposit : 0,
                ],
            ];
        })->filter()->values(); // Filter nulls and re-index

        // Fetch Partner Installments (Manual Plan)
        $installments = \App\Models\Partner\PartnerLearnerInstallment::where('learner_id', $learner->id)
            ->with('course') // meaningful info
            ->orderBy('due_date', 'asc')
            ->get();

        return view('partner.learners.show', compact('learner', 'enrolments', 'courses', 'installments'));
    }

    /**
     * Show Create Learner Form
     */
    public function create()
    {
        return view('partner.learners.create');
    }

    /**
     * Store New Learner
     */
    public function store(Request $request, \App\Services\MicrosoftGraphService $graphService)
    {
        $partner = Auth::user();

        // Validate Personal Email
        $request->validate([
            'first_name' => 'required|string|max:255',
            'sur_name' => 'required|string|max:255',
            'email_address' => 'required|email', // Check format. Uniqueness? Maybe in CRM details later.
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Create User in Portal DB with TEMP email (to get ID)
            // We use a safe temp generic email
            $tempEmail = 'temp_' . uniqid() . '@inspirecollegeoflearning.com';

            $learner = User::create([
                'org_id' => $partner->id,
                'status_id' => 1, // Pending
                'first_name' => $request->first_name,
                'sur_name' => $request->sur_name,
                'email_address' => $tempEmail,
                'password' => Hash::make($request->password),
            ]);

            // 2. Generate Correct DS Email
            $studentCode = config('app.student_email_prefix', 'ICOL') . $learner->id;
            $dsEmail = $studentCode . '@' . config('app.student_email_domain', 'inspirecollegeoflearning.com');

            // 3. Update Portal User with DS Email
            $learner->update([
                'email_address' => $dsEmail
            ]);

            // 4. Save Personal Email to CRM
            // Using the Crm\UserDetail model we created
            \App\Models\Crm\UserDetail::create([
                'learner_id' => $learner->id,
                'personal_email' => $request->email_address,
                // Add other fields if necessary or nullable
            ]);

            // 5. Create in Microsoft Graph (Disabled initially) using DS Email
            // Note: Password is set to user input as per requirement
            $msUser = $graphService->createPendingLearner($learner, $request->password);

            // 6. Update with MS ID
            $learner->update([
                'ms_user_id' => $msUser['id'],
                'ms_provisioned_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('partner.learners.index')
                ->with('success', "Learner created successfully. Login Email: {$dsEmail} (Pending Approval).");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error creating learner: ' . $e->getMessage())->withInput();
        }
    }
}
