<?php

namespace App\Console\Commands;

use App\Services\PaymentProcessingService;
use App\Services\StripeSessionService;
use Illuminate\Console\Command;
use Throwable;

class ReconcileStripeSession extends Command
{
    protected $signature = 'stripe:reconcile-session {session_id}';

    protected $description = 'Verify and reconcile one paid Stripe Checkout Session';

    public function handle(
        StripeSessionService $stripe, PaymentProcessingService $payments
    ): int {
        $sessionId = (string) $this->argument('session_id');
        try {
            $session = $stripe->retrieve($sessionId);
            if (($session->payment_status ?? null) !== 'paid') {
                $this->error('Stripe session is not paid; no records changed.');
                return self::FAILURE;
            }
            $result = $payments->processOrderPayment(
                $session, $session->metadata ?? []
            );
        } catch (Throwable $exception) {
            $this->error('Reconciliation failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $message = $result['already_processed']
            ? 'Session was already processed; payment records are unchanged.'
            : 'Session reconciled successfully.';
        $this->info($message);
        $this->table(['Record', 'ID'], [
            ['Order', $result['order_id']],
            ['Enrolment', $result['enrolment_id']],
            ['Installment', $result['installment_id'] ?? 'n/a'],
            ['Partner learner', $result['partner_learner_id'] ?? 'n/a'],
            ['Portal learner', $result['portal_learner_id'] ?? 'n/a'],
        ]);

        return self::SUCCESS;
    }
}
