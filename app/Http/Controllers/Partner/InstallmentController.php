<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\PartnerLearnerInstallment;
use App\Models\Crm\Order;
use App\Services\PaymentProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class InstallmentController extends Controller
{
    /**
     * List and Filter Installments
     */
    public function index(Request $request)
    {
        $partner = Auth::user();
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = \App\Models\Crm\Enrolment::where('partner_id', $partner->id)
            ->whereHas('partnerInstallments')
            ->with(['learner', 'course', 'partnerInstallments' => function($q) {
                $q->orderBy('due_date', 'asc');
            }]);

        // Apply Status Filters based on Enrolment installment access status
        if ($status === 'overdue') {
            $query->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid')->where('due_date', '<', now()->startOfDay());
            });
        } elseif ($status === 'due_soon') {
            $query->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid')->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()]);
            });
        } elseif ($status === 'pending') {
            $query->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid');
            });
        } elseif ($status === 'paid') {
            $query->whereDoesntHave('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid');
            });
        }

        // Apply Search (Learner name or email)
        if ($search) {
            $query->whereHas('learner', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('sur_name', 'like', "%{$search}%")
                    ->orWhere('email_address', 'like', "%{$search}%");
            });
        }

        $enrolments = $query->paginate(15)->withQueryString();

        // Counts for tabs
        $counts = [
            'all' => \App\Models\Crm\Enrolment::where('partner_id', $partner->id)->whereHas('partnerInstallments')->count(),
            'overdue' => \App\Models\Crm\Enrolment::where('partner_id', $partner->id)->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid')->where('due_date', '<', now()->startOfDay());
            })->count(),
            'due_soon' => \App\Models\Crm\Enrolment::where('partner_id', $partner->id)->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid')->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()]);
            })->count(),
            'pending' => \App\Models\Crm\Enrolment::where('partner_id', $partner->id)->whereHas('partnerInstallments', function($q) {
                $q->where('status', '!=', 'paid');
            })->count(),
        ];

        return view('partner.installments.index', compact('enrolments', 'counts', 'status', 'search'));
    }

    /**
     * Show details for a single enrolment's installments
     */
    public function show($enrolmentId)
    {
        $partner = Auth::user();
        $enrolment = \App\Models\Crm\Enrolment::where('id', $enrolmentId)
            ->where('partner_id', $partner->id)
            ->with(['learner', 'course', 'partnerInstallments' => function($q) {
                $q->orderBy('installment_no', 'asc');
            }])
            ->firstOrFail();

        return view('partner.installments.show', compact('enrolment'));
    }

    /**
     * Create Stripe Checkout Session for a specific installment
     */
    public function createCheckoutSession(Request $request, $installmentId)
    {
        $installment = PartnerLearnerInstallment::findOrFail($installmentId);
        $partner = Auth::user();
        $learner = $installment->learner; // Eager load if needed, or reliance on model relationship

        // Security: Ensure partner owns this learner? 
        // Assuming PartnerLearnerInstallment -> partner_id check is enough
        if ($installment->partner_id != $partner->id) {
            abort(403, 'Unauthorized');
        }

        if ($installment->status == 'paid') {
            return back()->with('error', 'This installment is already paid.');
        }

        // Amount to pay: remaining amount
        $amountToPay = $installment->installment_amount - $installment->paid_amount;
        if ($amountToPay <= 0) {
            return back()->with('error', 'No amount due for this installment.');
        }

        $description = "DS{$learner->id} " . ($installment->installment_no == 0 ? "Deposit" : "{$installment->installment_no}th Installment") . " Partner Payment for Learner";

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Ensure Stripe Customer for PARTNER
        if (empty($partner->stripe_customer_id)) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $partner->email_address,
                    'name' => "{$partner->first_name} {$partner->sur_name} (Partner)",
                    'metadata' => [
                        'partner_id' => $partner->id,
                        'type' => 'partner'
                    ]
                ]);
                $partner->stripe_customer_id = $customer->id;
                $partner->save();
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to create Stripe Customer for Partner: ' . $e->getMessage());
            }
        }

        try {
            $session = Session::create([
                'customer' => $partner->stripe_customer_id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'gbp',
                            'unit_amount' => (int) round($amountToPay * 100),
                            'product_data' => [
                                'name' => 'Installment Payment',
                                'description' => $description,
                                'metadata' => [
                                    'course_name' => $installment->course->title ?? 'Course',
                                    'course_id' => $installment->course->id ?? null,
                                    'installment_no' => $installment->installment_no,
                                    'learner_id' => $learner->id
                                ]
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                // Updated Metadata to ensure Partner-Paid flow tracking
                'metadata' => [
                    'payer_type' => 'partner',
                    'partner_id' => $partner->id,
                    'partner_email' => $partner->email_address,
                    'learner_id' => $learner->id,
                    'learner_email' => $learner->email_address, // Reference
                    'enrolment_id' => $installment->enrolment_id,
                    'order_id' => $installment->order_id,
                    'partner_installment_id' => $installment->id,
                    'type' => 'partner_installment_payment',
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'payer_type' => 'partner',
                        'partner_id' => $partner->id,
                        'learner_id' => $learner->id,
                        'enrolment_id' => $installment->enrolment_id,
                        'partner_installment_id' => $installment->id,
                    ],
                    'description' => $description,
                    'setup_future_usage' => 'off_session',
                ],
                'payment_method_options' => [
                    'card' => [
                        'setup_future_usage' => 'off_session',
                    ],
                ],
                'success_url' => route('partner.learners.show', $learner->id) . '?payment=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('partner.learners.show', $learner->id) . '?payment=cancelled',
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            return back()->with('error', 'Stripe Error: ' . $e->getMessage());
        }
    }

    /**
     * Record a manual payment for an installment.
     */
    public function storePayment(Request $request, $id, PaymentProcessingService $paymentService)
    {
        $partner = Auth::user();
        $installment = PartnerLearnerInstallment::findOrFail($id);

        // Authorization: Ensure Partner owns the installment
        if ($installment->partner_id !== $partner->id) {
            abort(403, 'Unauthorized.');
        }

        if (in_array($installment->status, ['awaiting_approval', 'proof_submitted'])) {
            return back()->with('error', 'A payment proof is already awaiting approval for this installment.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        DB::connection('mysql_crm')->beginTransaction();

        try {
            // Handle Receipt Upload
            $receiptPath = $installment->receipt_path; // Keep existing if not updated? Or append? Assuming replace.
            if ($request->hasFile('receipt')) {
                // Store in private or public? Ideally private, but for simplicity public/partners
                $path = $request->file('receipt')->store('partner_receipts', 'public');
                $receiptPath = $path;
            }

            // Requirement: Partner proof upload should NOT mark as paid.
            // It should be 'awaiting_approval' and amount stored in 'submitted_amount'.
            $installment->update([
                'submitted_amount' => $request->amount,
                'status' => 'awaiting_approval',
                'payment_reference' => $request->payment_reference,
                'receipt_path' => $receiptPath,
                'notes' => $request->notes,
            ]);

            // We do NOT update paid_amount or sync order/enrolment status here.
            // That must be done by the CRM Admin upon approval.

            Log::info("PARTNER_PROOF_SUBMITTED", [
                'installment_id' => $installment->id,
                'enrolment_id' => $installment->enrolment_id,
                'submitted_amount' => $request->amount
            ]);

            DB::connection('mysql_crm')->commit();

            return back()->with('success', 'Payment recorded successfully.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
}
