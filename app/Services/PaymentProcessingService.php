<?php

namespace App\Services;

use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use App\Models\Crm\PartnerLearner;
use App\Models\Partner\PartnerLearnerInstallment;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentProcessingService
{
    public function __construct(private readonly LearnerActivationService $activationService) {}

    public function processOrderPayment(object $session, object|array $metadata): array
    {
        $meta = $this->metadata($metadata);
        $context = $this->context($session, $meta);
        Log::info('STRIPE_PAYMENT_PROCESSING_STARTED', $context);

        if (($session->payment_status ?? null) !== 'paid') {
            throw new DomainException('Stripe Checkout Session is not paid.');
        }

        $learnerId = $this->id($meta['learner_id'] ?? null);
        $partnerLearnerId = $this->id($meta['partner_learner_id'] ?? null);
        if (! $learnerId && ! $partnerLearnerId) {
            Log::error('STRIPE_PAYMENT_OWNER_MISSING', $context);
            throw new DomainException('Neither learner_id nor partner_learner_id was supplied.');
        }
        if ($partnerLearnerId) {
            Log::info('PaymentProcessingService: Partner learner payment detected', $context);
        }

        $result = DB::connection('mysql_crm')->transaction(
            fn () => $this->processPaidSession($session, $meta, $context, $learnerId, $partnerLearnerId),
            3
        );

        return $this->activateAfterCommit($result, $context);
    }

    private function processPaidSession(
        object $session, array $meta, array $context, ?int $learnerId, ?int $partnerLearnerId
    ): array {
        $order = Order::query()->lockForUpdate()
            ->findOrFail($this->requiredId($meta, 'order_id'));
        $enrolment = Enrolment::query()->lockForUpdate()
            ->findOrFail($this->requiredId($meta, 'enrolment_id'));
        if ((int) $order->enrolment_id !== (int) $enrolment->id) {
            throw new DomainException('Order does not belong to enrolment.');
        }

        $partnerLearner = null;
        if ($partnerLearnerId) {
            $partnerLearner = PartnerLearner::query()->lockForUpdate()
                ->findOrFail($partnerLearnerId);
            $this->assertPartnerOwnership(
                $partnerLearner, $enrolment, $order, $this->id($meta['partner_id'] ?? null)
            );
        } else {
            $this->assertLearnerOwnership($learnerId, $enrolment, $order);
        }

        $intentId = $this->intentId($session);
        $query = DB::connection('mysql_crm')->table('payments')
            ->where('stripe_session_id', $session->id);
        if ($intentId) {
            $query->orWhere('stripe_payment_intent_id', $intentId);
        }
        $payment = $query->lockForUpdate()->first();
        if ($payment && $payment->status === 'paid') {
            Log::info('PARTNER_STRIPE_PAYMENT_CONFIRMED', $context + ['newly_processed' => false]);
            return $this->result($order, $enrolment, null, $partnerLearner, false, true);
        }

        $target = $this->paymentTarget($meta, $order, $enrolment);
        $amountPence = (int) ($session->amount_total ?? 0);
        $currency = strtolower((string) ($session->currency ?? ''));
        $expected = $this->outstandingPence($target, $order);
        if ($currency !== 'gbp') {
            Log::warning('STRIPE_PAYMENT_CURRENCY_MISMATCH', $context);
            throw new DomainException('Expected GBP, received '.$currency.'.');
        }
        if ($amountPence <= 0 || $amountPence !== $expected) {
            Log::warning('STRIPE_PAYMENT_AMOUNT_MISMATCH', $context + ['expected_pence' => $expected]);
            throw new DomainException('Stripe amount does not match the outstanding payment.');
        }

        $paidAt = $this->paidAt($session);
        $paymentData = [
            'student_id' => $learnerId,
            'order_id' => $order->id,
            'amount_pence' => $amountPence,
            'amount' => $amountPence / 100,
            'status' => 'paid',
            'method' => 'stripe',
            'currency' => 'GBP',
            'paid_at' => $paidAt,
            'payment_type' => 'stripe_checkout',
            'reference' => $session->id,
            'stripe_session_id' => $session->id,
            'stripe_payment_intent_id' => $intentId,
            'email' => $session->customer_details->email ?? null,
            'full_name' => $session->customer_details->name ?? null,
            'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];
        $payments = DB::connection('mysql_crm')->table('payments');
        if ($payment) {
            $payments->where('id', $payment->id)->update($paymentData);
        } else {
            $payments->insert($paymentData + ['created_at' => now()]);
        }

        $this->allocate($target, $order, $amountPence, $intentId, $session->id, $paidAt);
        $this->recalculate($order, $enrolment, $partnerLearner);
        Log::info('PARTNER_STRIPE_PAYMENT_CONFIRMED', $context + ['newly_processed' => true]);
        Log::info('ORDER_PAYMENT_ALLOCATED', $context + ['payment_target_id' => $target?->id]);
        Log::info('ORDER_STATUS_RECALCULATED', $context + [
            'order_status_id' => $order->fresh()->status_id,
            'payment_status' => $enrolment->fresh()->payment_status,
        ]);

        return $this->result($order, $enrolment, $target, $partnerLearner, true, false);
    }

    private function activateAfterCommit(array $result, array $context): array
    {
        $enrolment = Enrolment::findOrFail($result['enrolment_id']);
        if ($enrolment->activation_status === 'active') {
            $result['portal_learner_id'] = $enrolment->learner_id;
            $result['activated'] = true;
            return $result;
        }

        Log::info('PARTNER_LEARNER_ACTIVATION_STARTED', $context);
        $activated = $this->activationService->activate($enrolment);
        $result['portal_learner_id'] = $activated->learner_id;
        $result['activated'] = $activated->activation_status === 'active';
        Log::info('PARTNER_LEARNER_ACTIVATED', $context + [
            'portal_learner_id' => $activated->learner_id,
        ]);
        if ($activated->welcome_email_sent_at) {
            Log::info('PASSWORD_SETUP_EMAIL_SENT', $context + [
                'portal_learner_id' => $activated->learner_id,
            ]);
        }

        return $result;
    }

    private function paymentTarget(
        array $meta, Order $order, Enrolment $enrolment
    ): PartnerLearnerInstallment|OrderInstallment|null {
        if ($id = $this->id($meta['partner_installment_id'] ?? null)) {
            $target = PartnerLearnerInstallment::query()->lockForUpdate()->findOrFail($id);
        } elseif ($id = $this->id($meta['installment_id'] ?? null)) {
            $target = OrderInstallment::query()->lockForUpdate()->findOrFail($id);
        } else {
            $target = PartnerLearnerInstallment::query()
                ->where('order_id', $order->id)
                ->where('enrolment_id', $enrolment->id)
                ->where('status', '!=', 'paid');
            $target = $target->orderBy('installment_no')->orderBy('due_date')
                ->orderBy('id')->lockForUpdate()->first();
        }

        if ($target && (int) $target->order_id !== (int) $order->id) {
            throw new DomainException('Payment target does not belong to order.');
        }
        if ($target instanceof PartnerLearnerInstallment
            && (int) $target->enrolment_id !== (int) $enrolment->id) {
            throw new DomainException('Payment target does not belong to enrolment.');
        }

        return $target;
    }

    private function outstandingPence(
        PartnerLearnerInstallment|OrderInstallment|null $target, Order $order
    ): int {
        if ($target instanceof PartnerLearnerInstallment) {
            return $this->pence((float) $target->installment_amount - (float) $target->paid_amount);
        }
        if ($target instanceof OrderInstallment) {
            return $this->pence((float) $target->amount - (float) $target->amount_paid);
        }

        $due = $order->payment_mode === 'installment' && (float) $order->plan_deposit_amount > 0
            ? (float) $order->plan_deposit_amount - (float) $order->deposit_paid_amount
            : (float) $order->amount;
        return $this->pence($due);
    }

    private function allocate(
        $target, Order $order, int $pence, ?string $intentId,
        string $sessionId, CarbonImmutable $paidAt
    ): void {
        $amount = $pence / 100;
        if ($target instanceof PartnerLearnerInstallment) {
            $target->paid_amount = round((float) $target->paid_amount + $amount, 2);
            $target->status = (float) $target->paid_amount >= (float) $target->installment_amount
                ? 'paid' : 'partial';
            $target->paid_at = $target->status === 'paid' ? $paidAt : null;
            $target->stripe_payment_intent_id = $intentId;
            $target->payment_reference = $sessionId;
            $target->save();
            if ((int) $target->installment_no === 0) {
                $depositDue = (float) ($order->plan_deposit_amount ?: $order->amount);
                $order->deposit_paid_amount = min(
                    $depositDue, round((float) $order->deposit_paid_amount + $amount, 2)
                );
                $order->deposit_paid_at = (float) $order->deposit_paid_amount >= $depositDue
                    ? $paidAt : null;
            } else {
                $this->updateOrderInstallment(
                    $order, (int) $target->installment_no, $amount, $intentId, $paidAt
                );
            }
        } elseif ($target instanceof OrderInstallment) {
            $this->payOrderInstallment($target, $amount, $intentId, $paidAt);
        } elseif ($order->payment_mode === 'installment') {
            $depositDue = (float) $order->plan_deposit_amount;
            $order->deposit_paid_amount = min(
                $depositDue, round((float) $order->deposit_paid_amount + $amount, 2)
            );
            $order->deposit_paid_at = (float) $order->deposit_paid_amount >= $depositDue
                ? $paidAt : null;
        }

        $order->stripe_payment_id = $intentId;
        $order->save();
    }

    private function updateOrderInstallment(
        Order $order, int $number, float $amount, ?string $intentId, CarbonImmutable $paidAt
    ): void {
        $row = OrderInstallment::query()->where('order_id', $order->id)
            ->where('installment_no', $number)->lockForUpdate()->first();
        if ($row) {
            $this->payOrderInstallment($row, $amount, $intentId, $paidAt);
        }
    }

    private function result(
        Order $order, Enrolment $enrolment, $target, ?PartnerLearner $owner,
        bool $new, bool $already
    ): array {
        return [
            'order_id' => $order->id,
            'enrolment_id' => $enrolment->id,
            'installment_id' => $target?->id,
            'partner_learner_id' => $owner?->id,
            'portal_learner_id' => $enrolment->learner_id ?: null,
            'newly_processed' => $new,
            'already_processed' => $already,
            'activated' => false,
        ];
    }

    private function metadata(object|array $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }
        return method_exists($metadata, 'toArray')
            ? $metadata->toArray() : get_object_vars($metadata);
    }

    private function intentId(object $session): ?string
    {
        $intent = $session->payment_intent ?? null;
        return is_object($intent) ? ($intent->id ?? null) : ($intent ?: null);
    }

    private function paidAt(object $session): CarbonImmutable
    {
        $intent = $session->payment_intent ?? null;
        $timestamp = is_object($intent) ? ($intent->created ?? null) : null;
        $timestamp ??= $session->created ?? null;
        return $timestamp
            ? CarbonImmutable::createFromTimestampUTC((int) $timestamp)
            : CarbonImmutable::now();
    }

    private function requiredId(array $meta, string $key): int
    {
        $id = $this->id($meta[$key] ?? null);
        if (! $id) {
            throw new DomainException('Missing required Stripe metadata: '.$key);
        }
        return $id;
    }

    private function id(mixed $value): ?int
    {
        return $value === null || $value === '' || (string) $value === '0'
            ? null : (int) $value;
    }

    private function pence(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function context(object $session, array $meta): array
    {
        return [
            'stripe_session_id' => $session->id ?? null,
            'payment_intent_id' => $this->intentId($session),
            'order_id' => $this->id($meta['order_id'] ?? null),
            'enrolment_id' => $this->id($meta['enrolment_id'] ?? null),
            'partner_learner_id' => $this->id($meta['partner_learner_id'] ?? null),
            'learner_id' => $this->id($meta['learner_id'] ?? null),
            'amount_pence' => (int) ($session->amount_total ?? 0),
        ];
    }

    private function assertPartnerOwnership(
        PartnerLearner $owner, Enrolment $enrolment, Order $order, ?int $partnerId
    ): void {
        if (! $partnerId
            || (int) $owner->partner_id !== $partnerId
            || (int) $enrolment->partner_id !== $partnerId
            || (int) $enrolment->partner_learner_id !== (int) $owner->id
            || (int) $order->partner_learner_id !== (int) $owner->id) {
            throw new DomainException('Invalid partner learner, enrolment, order, or partner relationship.');
        }
    }

    private function assertLearnerOwnership(
        int $learnerId, Enrolment $enrolment, Order $order
    ): void {
        if (! User::find($learnerId)
            || (int) $enrolment->learner_id !== $learnerId
            || (int) $order->learner_id !== $learnerId) {
            throw new DomainException('Invalid learner, enrolment, or order relationship.');
        }
    }

    private function payOrderInstallment(
        OrderInstallment $row, float $amount, ?string $intentId, CarbonImmutable $paidAt
    ): void {
        $row->amount_paid = min(
            (float) $row->amount, round((float) $row->amount_paid + $amount, 2)
        );
        $row->payment_status = (float) $row->amount_paid >= (float) $row->amount
            ? 'paid' : 'partial';
        $row->paid_at = $row->payment_status === 'paid' ? $paidAt : null;
        $row->stripe_payment_id = $intentId;
        $row->save();
    }

    private function recalculate(
        Order $order, Enrolment $enrolment, ?PartnerLearner $partnerLearner
    ): void {
        $schedule = PartnerLearnerInstallment::query()
            ->where('order_id', $order->id)->lockForUpdate()->get();
        $fullyPaid = $schedule->isNotEmpty()
            ? $schedule->every(fn ($row) => $row->status === 'paid')
            : ! $order->installments()->where('payment_status', '!=', 'paid')->exists();
        $depositPaid = (float) $order->plan_deposit_amount <= 0
            || (float) $order->deposit_paid_amount >= (float) $order->plan_deposit_amount;

        if ($fullyPaid || $depositPaid) {
            $order->status_id = 1;
            $order->save();
        }

        $enrolment->payment_status = $fullyPaid ? 'paid' : 'partial';
        if ($fullyPaid || $depositPaid) {
            $active = EnrolmentStatus::query()->where('status', 'active')->first();
            if ($active) {
                $enrolment->status_id = $active->id;
            }
        }
        $enrolment->save();

        if ($partnerLearner) {
            $partnerLearner->payment_status = $fullyPaid ? 'paid' : 'partial';
            $partnerLearner->save();
        }
    }
}
