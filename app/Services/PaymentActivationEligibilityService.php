<?php

namespace App\Services;

use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentPricingSnapshot;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;
use Illuminate\Support\Facades\DB;

/** Central payment gate for initial learner activation. */
class PaymentActivationEligibilityService
{
    private const CONFIRMED_PAYMENT_STATUSES = ['paid', 'approved', 'successful', 'completed'];

    public function forEnrolment(Enrolment $enrolment): array
    {
        $order = Order::query()->where('enrolment_id', $enrolment->id)->orderByDesc('id')->lockForUpdate()->first();
        if (! $order) {
            return $this->result(false, null, 'unknown', 0, 0, 'No order is attached to this enrolment.');
        }

        $snapshot = EnrolmentPricingSnapshot::query()
            ->where('order_id', $order->id)
            ->orWhere(fn ($query) => $query->where('enrolment_id', $enrolment->id)->whereNull('order_id'))
            ->latest('id')->first();
        $planType = $this->planType($snapshot, $order);

        if ($this->isFullPaymentPlan($planType)) {
            $required = $this->minor($snapshot?->total_payable) ?: $this->minor($order->plan_full_amount) ?: $this->minor($order->amount);
            $payments = DB::connection('mysql_crm')->table('payments')->where('order_id', $order->id)
                ->whereIn(DB::raw('LOWER(status)'), self::CONFIRMED_PAYMENT_STATUSES)->get(['amount_pence', 'amount']);
            $confirmed = $payments->sum(fn ($payment) => $payment->amount_pence !== null ? (int) $payment->amount_pence : $this->minor($payment->amount));
            return $this->result($required > 0 && $confirmed >= $required, $order, $planType, $required, $confirmed);
        }

        $required = $this->minor($snapshot?->initial_deposit) ?: $this->minor($order->plan_deposit_amount);
        $deposit = PartnerLearnerInstallment::query()->where('order_id', $order->id)->where('enrolment_id', $enrolment->id)
            ->where('installment_no', 0)->lockForUpdate()->first();
        $required = $required ?: $this->minor($deposit?->deposit_amount) ?: $this->minor($deposit?->installment_amount);

        // A zero-upfront plan requires an explicit admissions approval; no pending schedule is treated as payment.
        if ($required === 0) {
            $approved = in_array(strtolower((string) $enrolment->payment_status), ['paid', 'approved'], true);
            return $this->result($approved, $order, $planType, 0, 0, $approved ? null : 'Zero-deposit plans require admissions approval.');
        }

        // The ledger-backed order field and the explicit deposit row are the only sources used.
        $confirmed = max(
            $this->minor($order->deposit_paid_amount),
            $deposit && in_array(strtolower((string) $deposit->status), self::CONFIRMED_PAYMENT_STATUSES, true) ? $this->minor($deposit->paid_amount) : 0,
        );
        return $this->result($confirmed >= $required, $order, $planType, $required, $confirmed);
    }

    private function result(bool $eligible, ?Order $order, string $planType, int $required, int $confirmed, ?string $reason = null): array
    {
        return compact('eligible', 'order', 'planType', 'required', 'confirmed', 'reason');
    }

    private function planType(?EnrolmentPricingSnapshot $snapshot, Order $order): string
    {
        return strtolower((string) ($snapshot?->selected_plan_type ?: data_get($snapshot?->snapshot_json, 'selected_payment_plan') ?: data_get($order->plan_meta, 'selected_payment_plan') ?: $order->payment_mode ?: 'unknown'));
    }

    private function isFullPaymentPlan(string $planType): bool
    {
        return in_array($planType, ['full', 'full_fee', 'full_payment', 'single', 'single_payment'], true);
    }

    /** Convert a stored decimal string to minor units without floating point. */
    private function minor(mixed $value): int
    {
        $value = trim((string) ($value ?? '0'));
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $match)) return 0;
        return ((int) $match[1] * 100) + (int) str_pad($match[2] ?? '', 2, '0');
    }
}
