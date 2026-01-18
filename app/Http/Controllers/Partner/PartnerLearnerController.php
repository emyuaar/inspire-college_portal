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
use Stripe\Checkout\Session AS StripeSession;
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
        if (! $partner->isOrganization()) {
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

        // Fetch Courses for the "Add Course" Modal
        $rawCourses = \App\Models\Website\Course::with(['promotions', 'activeCoursePromotion', 'activePromotion'])
            ->orderBy('title', 'asc')
            ->get();
        
        $courses = $rawCourses->map(function ($course) use ($pricingService) {
            return (object) $pricingService->getCoursePricing($course);
        });

        return view('partner.learners.show', compact('learner', 'enrolments', 'courses'));
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
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Create User in Portal DB with TEMP email (to get ID)
            // We use a safe temp generic email
            $tempEmail = 'temp_' . uniqid() . '@directskills.co.uk';

            $learner = User::create([
                'org_id' => $partner->id,
                'status_id' => 1, // Pending
                'first_name' => $request->first_name,
                'sur_name' => $request->sur_name,
                'email_address' => $tempEmail,
                'password' => Hash::make($request->password),
            ]);

            // 2. Generate Correct DS Email
            $dsEmail = "DS{$learner->id}@directskills.co.uk";

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
