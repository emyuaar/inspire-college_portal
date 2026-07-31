<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
// Requirement 5 says "Reuse existing... Do not introduce new frameworks...". 
use App\Services\StripeSessionService;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(\App\Services\PaymentProcessingService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request, StripeSessionService $stripe)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if (in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ], true)) {
            $session = $event->data->object;
            $metadata = $session->metadata ?? [];

            if (isset($metadata->type) && in_array($metadata->type, ['course_purchase', 'order_payment', 'partner_installment_payment'])) {
                // Delegate to Service
                try {
                    $this->paymentService->processOrderPayment($session, $metadata);
                } catch (\Exception $e) {
                     return response()->json(['error' => $e->getMessage()], 500);
                }
            }
        } elseif ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $session = $stripe->findByPaymentIntent($intent->id);
            $metadata = $session?->metadata ?? null;
            if ($metadata && isset($metadata->type)
                && in_array($metadata->type, [
                    'course_purchase', 'order_payment', 'partner_installment_payment',
                ], true)) {
                try {
                    $this->paymentService->processOrderPayment($session, $metadata);
                } catch (\Exception $exception) {
                    return response()->json(['error' => $exception->getMessage()], 500);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
