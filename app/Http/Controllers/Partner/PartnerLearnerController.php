<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\PartnerLearner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->with(['enrolments', 'enrolments.status']) // Assuming relationships exist or will filter in view
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

        if (str_starts_with((string) $id, 'pending-')) {
            $isPending = true;
            $pendingId = (int) str_replace('pending-', '', (string) $id);
            $pendingLearner = PartnerLearner::where('partner_id', $partner->id)->findOrFail($pendingId);

            if ($pendingLearner->user_id && $pendingLearner->activation_status === 'active') {
                $activeLearner = User::myLearners($partner->id)->find($pendingLearner->user_id);
                if ($activeLearner) {
                    $learner = $activeLearner;
                    $isPending = false;
                } else {
                    $learner = $pendingLearner;
                }
            } else {
                $learner = $pendingLearner;
            }
        } else {
            $learner = User::myLearners($partner->id)->find($id);

            if (! $learner) {
                $learner = PartnerLearner::where('partner_id', $partner->id)->findOrFail($id);
                $isPending = true;
            }
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

        $enrolments = Enrolment::query()
            ->when($isPending, fn ($query) => $query->where('partner_learner_id', $learner->id))
            ->when(! $isPending, fn ($query) => $query->where('learner_id', $learner->id))
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
        $installments = \App\Models\Partner\PartnerLearnerInstallment::whereIn('enrolment_id', $enrolments->pluck('id'))
            ->with('course') // meaningful info
            ->orderBy('due_date', 'asc')
            ->get();

        return view('partner.learners.show', compact('learner', 'enrolments', 'courses', 'installments', 'isPending'));
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
    public function store(Request $request)
    {
        $partner = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'sur_name' => 'required|string|max:60',
            'email_address' => 'required|email',
            'contact_number' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:30',
        ]);

        DB::connection('mysql_crm')->beginTransaction();

        try {
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

            return redirect()->route('partner.learners.show', 'pending-' . $partnerLearner->id)
                ->with('success', 'Learner profile created successfully. Add a course and submit payment to continue.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Error creating learner: ' . $e->getMessage())->withInput();
        }
    }
}
