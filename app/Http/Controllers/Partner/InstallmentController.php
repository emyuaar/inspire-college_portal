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

        $description = "ICOL{$learner->id} " . ($installment->installment_no == 0 ? "Deposit" : "{$installment->installment_no}th Installment") . " Partner Payment for Learner";

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
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

            $installment->update([
                'submitted_amount' => $request->amount,
                'status' => 'awaiting_approval',
                'payment_reference' => $request->payment_reference,
                'receipt_path' => $receiptPath,
                'notes' => $request->notes,
            ]);

            $proof = \App\Models\Partner\PartnerPaymentProof::create([
                'partner_learner_installment_id' => $installment->id,
                'partner_id' => $installment->partner_id,
                'learner_id' => $installment->learner_id,
                'enrolment_id' => $installment->enrolment_id,
                'order_id' => $installment->order_id,
                'course_id' => $installment->course_id,
                'submitted_amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_reference' => $request->payment_reference,
                'proof_path' => $receiptPath,
                'notes' => $request->notes,
                'status' => 'awaiting_approval',
            ]);

            try {
                app(\App\Services\CrmNotificationService::class)->notifyAdminsForProofSubmission($proof);
            } catch (\Throwable $e) {
                Log::error('CRM_NOTIFICATION_FAILED: ' . $e->getMessage());
            }

            Log::info('PARTNER_PROOF_SUBMITTED', [
                'installment_id' => $installment->id,
                'enrolment_id' => $installment->enrolment_id,
                'submitted_amount' => $request->amount,
            ]);

            DB::connection('mysql_crm')->commit();

            return back()->with('success', 'Payment proof submitted successfully. Admissions will review it shortly.');

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
}
