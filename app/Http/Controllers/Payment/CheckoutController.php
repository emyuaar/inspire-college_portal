<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Crm\PartnerLearner;

class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request, $learnerId)
    {
        $partner = Auth::user();

        $learner = null;
        $partnerLearner = null;
        $isPending = false;

        if (str_starts_with($learnerId, 'pending-')) {
            $realId = str_replace('pending-', '', $learnerId);
            $partnerLearner = PartnerLearner::where('partner_id', $partner->id)->findOrFail($realId);
            $isPending = true;
        } else {
            $learner = User::myLearners($partner->id)->findOrFail($learnerId);
            if (!$learner->crm_approved) {
                return back()->with('error', 'Learner is not approved for payment yet.');
            }
        }

        // 2. Validate Request (Enrolment ID)
        $request->validate([
            'enrolment_id' => 'required|integer'
        ]);

        $enrolmentQuery = Enrolment::query();
        if ($isPending) {
            $enrolmentQuery->where('partner_learner_id', $partnerLearner->id);
        } else {
            $enrolmentQuery->where('learner_id', $learner->id);
        }
        $enrolment = $enrolmentQuery->findOrFail($request->enrolment_id);

        Log::info("PayNow: Checking for pending order", [
            'enrolment_id' => $enrolment->id,
            'learner_id' => $isPending ? null : $learner->id,
            'partner_learner_id' => $isPending ? $partnerLearner->id : null
        ]);

        // 3. Find Pending Order
        $order = Order::where('enrolment_id', $enrolment->id)
            ->where(function ($q) {
                $q->whereNull('status_id')
                  ->orWhere('status_id', 0)
                  ->orWhere('status_id', 3); // 3 = Pending in CRM
            })
            ->orderBy('id', 'desc')
            ->first();

        if (!$order) {
            Log::warning("PayNow: No pending order found", [
                'enrolment_id' => $enrolment->id,
                'learner_id' => $isPending ? null : $learner->id,
                'partner_learner_id' => $isPending ? $partnerLearner->id : null
            ]);
            // Check if already paid?
            // For now, assume if no pending order, nothing to pay.
            return back()->with('error', 'No pending payment found for this course. (Order not Pending)');
        }

        Log::info("PayNow: Found pending order #{$order->id} with amount {$order->amount}");

        // 4. Determine Amount (Installment vs Full)
        // FIX: Always pay the Order amount (which is Deposit or Full Price).
        // Since the Order is "Pending" (checked above), this IS the initial transaction.
        // Do not look at installments yet (they are for future).
        $amountToPay = ($order->payment_mode === 'installment' && $order->plan_deposit_amount > 0) 
            ? $order->plan_deposit_amount 
            : $order->amount;
        $description = "Course Purchase: {$enrolment->course->title}" . ($order->payment_mode === 'installment' ? " (Deposit)" : "");
        $installmentId = null;

        // REMOVED: Loop looking for first pending installment.
        // Reason: Installments are now strictly "Future Months".
        // The "Deposit" is stored in the Order itself.

        // 5. Create Stripe Session
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // STRIPE CUSTOMER LOGIC
        $stripeCustomerId = $isPending ? null : $learner->stripe_customer_id;
        $emailForStripe = $isPending ? $partnerLearner->personal_email : $learner->email_address;
        $nameForStripe = $isPending ? "{$partnerLearner->first_name} {$partnerLearner->last_name}" : "{$learner->first_name} {$learner->sur_name}";

        if (!$isPending && empty($stripeCustomerId)) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $emailForStripe,
                    'name' => $nameForStripe,
                    'metadata' => [
                        'learner_id' => $learner->id,
                        'partner_id' => $partner->id
                    ]
                ]);

                $learner->stripe_customer_id = $customer->id;
                $learner->save();
                $stripeCustomerId = $customer->id;
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to create Stripe Customer: ' . $e->getMessage());
            }
        }

        // COUPON LOGIC
        $couponId = null;
        $discounts = [];

        if ($request->filled('coupon_code')) {
            // Re-validate coupon server-side for security
            $coupon = \Illuminate\Support\Facades\DB::connection('mysql_website')->table('coupons')
                ->where('code', $request->coupon_code)
                ->first();

            if ($coupon && $coupon->is_active) {
                // Scope Check
                $validScope = true;
                if ($coupon->connected && $coupon->connected_type === 'company') {
                    if ($partner->org_id != $coupon->connected_id) {
                        $validScope = false;
                    }
                }

                // Expiry Check
                if ($coupon->expires_at && \Carbon\Carbon::parse($coupon->expires_at)->isPast()) {
                    $validScope = false;
                }

                if ($validScope && $coupon->stripe_promo_code_id) {
                    $discounts[] = ['promotion_code' => $coupon->stripe_promo_code_id];
                    $couponId = $coupon->id;
                }
            }
        }

        try {
            $sessionPayload = [
                'customer' => $stripeCustomerId, // May be null for pending
                'customer_email' => $stripeCustomerId ? null : $emailForStripe,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'gbp',
                            'unit_amount' => (int) round($amountToPay * 100),
                            'product_data' => [
                                'name' => 'DirectSkills Course Payment',
                                'description' => $description,
                                'metadata' => [
                                    'course_name' => $enrolment->course->title ?? 'Course',
                                    'learner_name' => $nameForStripe
                                ]
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => route('partner.learners.show', $learnerId) . '?payment=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('partner.learners.show', $learnerId) . '?payment=cancelled',
                'metadata' => [
                    'partner_id' => $partner->id,
                    'learner_id' => $isPending ? null : $learner->id,
                    'partner_learner_id' => $isPending ? $partnerLearner->id : null,
                    'enrolment_id' => $enrolment->id,
                    'order_id' => $order->id,
                    'installment_id' => $installmentId, // Nullable
                    'type' => 'order_payment',
                    'coupon_id' => $couponId,
                ],
            ];

            if (!empty($discounts)) {
                $sessionPayload['discounts'] = $discounts;
            } else {
                // optional: allow promotion codes to be entered AT stripe checkout too?
                // $sessionPayload['allow_promotion_codes'] = true; 
                // User said "Pass Stripe Promotion Code into checkout", implying pre-applied.
            }

            $session = Session::create($sessionPayload);

            return redirect($session->url);

        } catch (\Exception $e) {
            return back()->with('error', 'Stripe Error: ' . $e->getMessage());
        }
    }
}
