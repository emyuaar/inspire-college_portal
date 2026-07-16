<?php

namespace App\Services;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeSessionService
{
    public function retrieve(string $sessionId): object
    {
        Stripe::setApiKey((string) env('STRIPE_SECRET'));
        return Session::retrieve([
            'id' => $sessionId,
            'expand' => ['payment_intent'],
        ]);
    }

    public function findByPaymentIntent(string $intentId): ?object
    {
        Stripe::setApiKey((string) env('STRIPE_SECRET'));
        $sessions = Session::all([
            'payment_intent' => $intentId,
            'limit' => 1,
            'expand' => ['data.payment_intent'],
        ]);
        return $sessions->data[0] ?? null;
    }
}
