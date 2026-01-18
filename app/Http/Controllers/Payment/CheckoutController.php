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

class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request, $learnerId)
    {
        $partner = Auth::user();
        
        // 1. Find Learner & Validate Ownership
        $learner = User::myLearners($partner->id)->findOrFail($learnerId);

        if (! $learner->crm_approved) {
             return back()->with('error', 'Learner is not approved for payment yet.');
        }

        // 2. Validate Request (Enrolment ID)
        $request->validate([
            'enrolment_id' => 'required|integer'
        ]);

        $enrolment = Enrolment::where('learner_id', $learner->id)
            ->findOrFail($request->enrolment_id);

        // 3. Find Pending Order
        $order = Order::where('enrolment_id', $enrolment->id)
            ->whereNull('status_id') // NULL = Pending (1 = Paid)
            ->orderBy('id', 'desc')
            ->first();

        if (!$order) {
            // Check if already paid?
            // For now, assume if no pending order, nothing to pay.
            return back()->with('error', 'No pending payment found for this course.');
        }

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
                    'name'  => "{$learner->first_name} {$learner->sur_name}",
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

        try {
            $session = Session::create([
                'customer' => $learner->stripe_customer_id, // Link to persistent customer
                'payment_method_types' => ['card'],
                'line_items' => [[
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
                ]],
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
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            return back()->with('error', 'Stripe Error: ' . $e->getMessage());
        }
    }
}
