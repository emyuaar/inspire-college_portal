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

class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request, $learnerId)
    {
        $partner = Auth::user();

        // 1. Find Learner & Validate Ownership
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        if (!$learner->crm_approved) {
            return back()->with('error', 'Learner is not approved for payment yet.');
        }

        // 2. Validate Request (Enrolment ID)
        $request->validate([
            'enrolment_id' => 'required|integer'
        ]);

        $enrolment = Enrolment::where('learner_id', $learner->id)
            ->findOrFail($request->enrolment_id);

        Log::info("PayNow: Checking for pending order", [
            'enrolment_id' => $enrolment->id,
            'learner_id' => $learner->id
        ]);

        // 3. Find Pending Order
        $order = Order::where('enrolment_id', $enrolment->id)
            ->where(function ($q) {
                $q->whereNull('status_id')->orWhere('status_id', 0);
            })
            ->orderBy('id', 'desc')
            ->first();

        if (!$order) {
            Log::warning("PayNow: No pending order found", [
                'enrolment_id' => $enrolment->id,
                'learner_id' => $learner->id
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
        $amountToPay = $order->amount;
        $description = "Course Purchase: {$enrolment->course->title}";
        $installmentId = null;

        // REMOVED: Loop looking for first pending installment.
        // Reason: Installments are now strictly "Future Months".
        // The "Deposit" is stored in the Order itself.

        // 5. Create Stripe Session
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // ENSURE STRIPE CUSTOMER EXISTS
        if (empty($learner->stripe_customer_id)) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $learner->email_address, // Use Portal email (DS...) or Personal? Ideally Personal if we have it, but Portal Email is safer as unique key.
                    'name' => "{$learner->first_name} {$learner->sur_name}",
                    'metadata' => [
                        'learner_id' => $learner->id,
                        'partner_id' => $partner->id
                    ]
                ]);

                $learner->stripe_customer_id = $customer->id;
                $learner->save();
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
                'customer' => $learner->stripe_customer_id, // Link to persistent customer
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'gbp',
                            // Stripe expects pence/cents
                            'unit_amount' => (int) round($amountToPay * 100),
                            'product_data' => [
                                'name' => 'DirectSkills Course Payment',
                                'description' => $description,
                                'metadata' => [
                                    'course_name' => $enrolment->course->title ?? 'Course',
                                    'learner_name' => "{$learner->first_name} {$learner->sur_name}"
                                ]
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => route('partner.learners.show', $learner->id) . '?payment=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('partner.learners.show', $learner->id) . '?payment=cancelled',
                'metadata' => [
                    'partner_id' => $partner->id,
                    'learner_id' => $learner->id,
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
