<?php

namespace App\Services;

use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Price;
use Stripe\Subscription;

class PaymentProcessingService
{
    protected $graphService;

    public function __construct(MicrosoftGraphService $graphService)
    {
        $this->graphService = $graphService;
    }

    public function processOrderPayment($session, $metadata)
    {
        // Debug Logging
        Log::info("PaymentProcessingService: Starting for Session {$session->id}", [
            'payment_status' => $session->payment_status,
            'metadata_raw' => $metadata,
        ]);

        // Ensure metadata is accessible as object or array
        // Stripe metadata is StripeObject. Convert to array then object for consistency if needed, 
        // or just access as array if it was passed as such. 
        // In Webhook it is passed as $session->metadata (StripeObject).
        // In Controller it is passed as $session->metadata (StripeObject).

        $metaArray = is_object($metadata) && method_exists($metadata, 'toArray') ? $metadata->toArray() : (array) $metadata;

        $learnerId = $metaArray['learner_id'] ?? null;
        $orderId = $metaArray['order_id'] ?? null;
        $installmentId = $metaArray['installment_id'] ?? null;
        $enrolmentId = $metaArray['enrolment_id'] ?? null;
        $partnerInstallmentId = $metaArray['partner_installment_id'] ?? null;

        Log::info("PaymentProcessingService: Extracted IDs", [
            'learner_id' => $learnerId,
            'order_id' => $orderId,
            'installment_id' => $installmentId,
            'enrolment_id' => $enrolmentId,
            'partner_installment_id' => $partnerInstallmentId,
        ]);

        if (!$learnerId) {
            Log::error("PaymentProcessingService: No learner_id found in metadata. Aborting.");
            return false;
        }

        DB::connection('mysql_crm')->beginTransaction();
        DB::connection('mysql_portal')->beginTransaction();

        try {
            // 1. Record Payment in CRM (Avoid duplicates if possible, strictly insert for log)
            // Check if payment already recorded for this session?
            $existingPayment = DB::connection('mysql_crm')->table('payments')
                ->where('stripe_session_id', $session->id)
                ->first();

            if (!$existingPayment) {
                DB::connection('mysql_crm')->table('payments')->insertGetId([
                    'stripe_session_id' => $session->id,
                    'amount_pence' => $session->amount_total,
                    'email' => $session->customer_details->email ?? null,
                    'full_name' => $session->customer_details->name ?? null,
                    'status' => 'succeeded',
                    'payment_type' => 'stripe_checkout',
                    'meta' => json_encode($metadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Update Order / Installment
            if ($partnerInstallmentId) {
                // Paying a Partner Manual Installment (via Stripe)
                $pInstallment = \App\Models\Partner\PartnerLearnerInstallment::find($partnerInstallmentId);

                if ($pInstallment && $pInstallment->status != 'paid') {
                    $pInstallment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'paid_amount' => $pInstallment->installment_amount, // Assume full payment for now via Stripe
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'payment_reference' => $session->id,
                    ]);


                    Log::info("PaymentProcessingService: Updated Partner Installment {$partnerInstallmentId} to PAID.");

                    // SYNC ORDER STATUS (Stripe Flow)
                    // Ensure the related order is updated to Active/Paid (1)
                    $order = null;
                    if ($pInstallment->order_id) {
                        $order = Order::find($pInstallment->order_id);
                    }
                    if (!$order && $pInstallment->enrolment_id) {
                        $order = Order::where('enrolment_id', $pInstallment->enrolment_id)->orderByDesc('id')->first();
                    }

                    if ($order) {
                        // Check if all installments are paid to decide 1 (Paid) vs 1 (Active)
                        // For simplicity and user requirement "orders.status_id = 1", we set to 1.
                        $order->update([
                            'status_id' => 1,
                            'payment_mode' => 'installment'
                        ]);
                        Log::info("PaymentProcessingService: Updated Order {$order->id} status to 1 (Active/Paid).");
                    } else {
                        // Create missing order logic only if really needed, but typically Stripe payment implies order exists or was just created via Checkout/Plan.
                        // If it came from Partner Portal "Pay" link, order might be missing if imported? 
                        // Let's create if missing as good measure.
                        $order = Order::create([
                            'learner_id' => $pInstallment->learner_id,
                            'enrolment_id' => $pInstallment->enrolment_id,
                            'amount' => $pInstallment->total_amount ?? 0,
                            'status_id' => 1, // Paid/Active
                            'payment_mode' => 'installment',
                            'plan_title' => 'Installment Plan (Auto-Created via Stripe)',
                        ]);
                        Log::info("PaymentProcessingService: Created Missing Order {$order->id} and set to 1.");
                    }
                }
            } elseif ($installmentId) {
                // Paying specific installment (Old/Public Logic)
                $installment = OrderInstallment::find($installmentId);
                if ($installment && $installment->payment_status != 'paid') {
                    $installment->update([
                        'payment_status' => 'paid',
                        'stripe_payment_id' => $session->payment_intent,
                    ]);
                }
            } elseif ($orderId) {
                // Paying full order (Deposit or Full Price)
                $order = Order::find($orderId);
                if ($order && $order->status_id != 1) { // If not already paid
                    $order->update([
                        'status_id' => 1, // Paid
                        'stripe_payment_id' => $session->payment_intent,
                    ]);

                    // --- INSTALLMENT SUBSCRIPTION LOGIC ---
                    // If this was a Deposit for an Installment Plan, we must create the Subscription now.
                    // Check for pending installments linked to this order.
                    $pendingInstallments = $order->installments()->where('payment_status', 'pending')->count();
                    $firstInstallment = $order->installments()->where('payment_status', 'pending')->first();

                    Log::info('PaymentProcessingService: Checking for Installment Subscription', [
                        'order_id' => $order->id,
                        'pending_installments' => $pendingInstallments,
                        'first_installment_amount' => $firstInstallment ? $firstInstallment->amount : null,
                        'stripe_subscription_id' => $order->stripe_subscription_id,
                    ]);

                    if ($pendingInstallments > 0 && $firstInstallment && empty($order->stripe_subscription_id)) {
                        try {
                            $learner = User::find($learnerId);
                            $enrolment = Enrolment::find($order->enrolment_id);
                            $course = $enrolment->course;

                            Log::info('PaymentProcessingService: Attempting to create subscription', [
                                'learner_id' => $learnerId,
                                'stripe_customer_id' => $learner->stripe_customer_id,
                                'course_id' => $course->id,
                            ]);

                            // Stripe Customer ID is required
                            if ($learner->stripe_customer_id) {
                                // 1. Create Price
                                $monthlyAmount = $firstInstallment->amount; // Assuming all remaining are equal
                                $priceId = $this->createStripePriceForInstallmentsAmount($course, $order, $learner, $monthlyAmount);

                                Log::info('PaymentProcessingService: Created Stripe Price', ['price_id' => $priceId]);

                                // 2. Create Subscription
                                $subscription = $this->createStripeSubscriptionForInstallmentsMonths(
                                    $learner->stripe_customer_id,
                                    $order,
                                    $learner,
                                    $priceId,
                                    $pendingInstallments
                                );

                                if ($subscription && $subscription->id) {
                                    $order->update(['stripe_subscription_id' => $subscription->id]);
                                    Log::info("PaymentProcessingService: Created Stripe Subscription {$subscription->id} for Order {$order->id}");
                                } else {
                                    Log::warning("PaymentProcessingService: Subscription creation returned null/no ID.");
                                }
                            } else {
                                Log::warning("Skipping Subscription: No stripe_customer_id for user {$learnerId}");
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to create Stripe Subscription: " . $e->getMessage(), [
                                'trace' => $e->getTraceAsString()
                            ]);
                            // Do not fail the whole transaction, just log. Admin can fix.
                        }
                    } else {
                        Log::info('PaymentProcessingService: Skipping subscription creation (No pending installments or already exists).');
                    }
                }
            }

            // 3. Update Enrolment Status (Strict Activation Logic)
            if ($orderId || $installmentId || $partnerInstallmentId) {
                // $enrolmentId extracted above
                if ($enrolmentId) {
                    $enrolment = Enrolment::find($enrolmentId);

                    if ($enrolment) {
                        Log::info("PaymentProcessingService: Updating Enrolment {$enrolmentId}");
                        // Check Requirements
                        $onboarding = \App\Models\Crm\LearnerOnboardingStatus::where('learner_id', $learnerId)->first();
                        $requirementsMet = $onboarding &&
                            $onboarding->personal_info_completed &&
                            $onboarding->rpl_info_completed &&
                            $onboarding->disability_info_completed;

                        // Check CRM Approval
                        $user = User::find($learnerId);
                        $isCrmApproved = $user && $user->crm_approved;

                        if ($requirementsMet && $isCrmApproved) {
                            $activeStatus = EnrolmentStatus::firstOrCreate(['status' => 'active']);
                            $enrolment->update(['status_id' => $activeStatus->id]);
                            Log::info("PaymentProcessingService: Enrolment set to Active.");
                        } else {
                            // Payment received, but requirements/approval missing -> Set to Pending (1)
                            $pendingStatus = EnrolmentStatus::firstOrCreate(['status' => 'pending']);
                            $enrolment->update(['status_id' => $pendingStatus->id]);
                            Log::info("PaymentProcessingService: Enrolment set to Pending (Requirements/Approval missing).");
                        }
                    } else {
                        Log::warning("PaymentProcessingService: Enrolment ID {$enrolmentId} not found.");
                    }
                }
            }

            // 4. Activate User & Provision MS License (Run ALWAYS on paid)
            $learner = User::find($learnerId);
            if ($learner) {
                Log::info("PaymentProcessingService: Activating Learner {$learnerId} and Provisioning MS.");

                // Always mark active if paid
                if ($learner->status_id != 2) {
                    $learner->update(['status_id' => 2]); // Active
                }

                // Always try provisioning (Service handles idempotency / existing checks)
                // BUT we should avoid API calls if we mistakenly believe it's done? 
                // No, rely on Service to check. Or check local flag.
                if (!$learner->ms_license_assigned) {
                    try {
                        $this->graphService->provisionLearner($learner);
                        Log::info("PaymentProcessingService: MS Provisioning triggered successfully.");
                    } catch (\Exception $e) {
                        Log::error("PaymentProcessingService: Failed to provision MS user {$learnerId}: " . $e->getMessage());
                        // We do NOT re-throw, to avoid rolling back the Payment record. Valid payment should persist.
                    }
                } else {
                    Log::info("PaymentProcessingService: MS License already assigned. Skipping.");
                }
            }

            DB::connection('mysql_crm')->commit();
            DB::connection('mysql_portal')->commit();

            return true;

        } catch (\Exception $e) {
            DB::connection('mysql_crm')->rollBack();
            DB::connection('mysql_portal')->rollBack();
            Log::error("Payment Processing Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: create Stripe price with passed monthly amount
     */
    protected function createStripePriceForInstallmentsAmount($course, $order, $learner, float $monthlyAmount): string
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $price = Price::create([
            'unit_amount' => (int) round($monthlyAmount * 100),
            'currency' => 'gbp',
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
            ],
            'product_data' => [
                'name' => $course->title . ' (Installment)',
            ],
            'metadata' => [
                'order_id' => $order->id,
                'course_id' => $course->id,
                'learner_id' => $learner->id,
            ],
        ]);

        return $price->id;
    }

    /**
     * Helper: create subscription with passed months
     */
    protected function createStripeSubscriptionForInstallmentsMonths(
        string $customerId,
        Order $order,
        User $learner,
        string $priceId,
        int $months
    ): ?Subscription {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $trialEnd = now()->addMonth()->timestamp;
        $cancelAt = now()->addMonths($months)->timestamp;

        $subscription = Subscription::create([
            'customer' => $customerId,
            'items' => [
                ['price' => $priceId],
            ],
            'trial_end' => $trialEnd,
            'payment_behavior' => 'default_incomplete',
            'cancel_at' => $cancelAt,
            'metadata' => [
                'order_id' => $order->id,
                'learner_id' => $learner->id,
            ],
        ]);

        return isset($subscription->id) ? $subscription : null;
    }
}
