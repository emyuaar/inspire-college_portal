<?php

namespace App\Services;

use App\Models\Crm\PartnerAssignedCourse;
use App\Models\Crm\PartnerCoursePaymentPlan;
use App\Models\Website\Course;
use DomainException;

class PartnerCoursePricingService
{
    public function quote(Course $course, PartnerAssignedCourse $assignment, int $partnerId): array
    {
        $this->assertAssignmentIsUsable($course, $assignment, $partnerId);

        $originalMinor = $this->toMinorUnits($this->originalFee($course));
        $discountType = $assignment->discount_type ?: 'percentage';
        $discountValue = (string) ($assignment->discount_value ?? '0');

        if (!in_array($discountType, ['percentage', 'fixed'], true)) {
            throw new DomainException('The partner discount type is invalid.');
        }

        if ($discountType === 'percentage') {
            $discountBasisPoints = $this->toScaledInteger($discountValue, 2);

            if ($discountBasisPoints < 0 || $discountBasisPoints > 10000) {
                throw new DomainException('The partner percentage discount must be between 0 and 100.');
            }

            $discountMinor = intdiv(($originalMinor * $discountBasisPoints) + 5000, 10000);
        } else {
            $discountMinor = $this->toMinorUnits($discountValue);

            if ($discountMinor < 0 || $discountMinor > $originalMinor) {
                throw new DomainException('The fixed partner discount cannot exceed the original course fee.');
            }
        }

        $finalMinor = $originalMinor - $discountMinor;

        if ($finalMinor < 0) {
            throw new DomainException('The final partner course fee cannot be negative.');
        }

        return [
            'partner_id' => $partnerId,
            'course_id' => (int) $course->id,
            'partner_course_assignment_id' => (int) $assignment->id,
            'original_fee_minor' => $originalMinor,
            'original_course_fee' => $this->fromMinorUnits($originalMinor),
            'partner_discount_type' => $discountType,
            'partner_discount_value' => $this->normaliseDecimal($discountValue),
            'partner_discount_amount_minor' => $discountMinor,
            'partner_discount_amount' => $this->fromMinorUnits($discountMinor),
            'final_fee_minor' => $finalMinor,
            'final_course_fee' => $this->fromMinorUnits($finalMinor),
        ];
    }

    public function buildPlan(
        PartnerAssignedCourse $assignment,
        string $planType,
        array $quote,
        ?PartnerCoursePaymentPlan $customPlan = null
    ): array {
        if ($planType === 'full') {
            if (!$assignment->allow_full_payment) {
                throw new DomainException('Full payment is not enabled for this partner course.');
            }

            return $this->planFromAmounts('full', 'Full Payment', 'full', [$quote['final_fee_minor']]);
        }

        if ($planType === 'two_months') {
            if (!$assignment->allow_two_months) {
                throw new DomainException('The 2-month payment plan is not enabled for this partner course.');
            }

            return $this->planFromAmounts(
                'two_months',
                '2 Months',
                'installment',
                $this->splitMinorUnits($quote['final_fee_minor'], 2)
            );
        }

        if ($planType === 'three_months') {
            if (!$assignment->allow_three_months) {
                throw new DomainException('The 3-month payment plan is not enabled for this partner course.');
            }

            return $this->planFromAmounts(
                'three_months',
                '3 Months',
                'installment',
                $this->splitMinorUnits($quote['final_fee_minor'], 3)
            );
        }

        if (!$assignment->allow_installments || !$customPlan || (int) $customPlan->pac_id !== (int) $assignment->id || !$customPlan->status) {
            throw new DomainException('The selected payment plan is not enabled for this partner course.');
        }

        $monthlyCount = (int) $customPlan->months;
        if ($monthlyCount < 1) {
            throw new DomainException('The selected custom payment plan is invalid.');
        }

        $depositMinor = $this->toMinorUnits((string) ($customPlan->deposit ?? '0'));
        if ($depositMinor < 0 || $depositMinor > $quote['final_fee_minor']) {
            throw new DomainException('The custom plan deposit cannot exceed the discounted course fee.');
        }

        $remaining = $quote['final_fee_minor'] - $depositMinor;
        $amounts = $depositMinor > 0
            ? array_merge([$depositMinor], $this->splitMinorUnits($remaining, $monthlyCount))
            : $this->splitMinorUnits($remaining, $monthlyCount);

        return $this->planFromAmounts(
            'installment_' . $customPlan->id,
            'Installment Plan',
            'installment',
            $amounts
        );
    }

    public function splitMinorUnits(int $totalMinor, int $installmentCount): array
    {
        if ($totalMinor < 0 || $installmentCount < 1) {
            throw new DomainException('Invalid installment split.');
        }

        $base = intdiv($totalMinor, $installmentCount);
        $remainder = $totalMinor - ($base * $installmentCount);
        $amounts = [];

        for ($index = 0; $index < $installmentCount; $index++) {
            $amounts[] = $base + ($index < $remainder ? 1 : 0);
        }

        return $amounts;
    }

    public function toMinorUnits(string|int $amount): int
    {
        return $this->toScaledInteger((string) $amount, 2);
    }

    public function fromMinorUnits(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absolute = abs($amount);

        return $sign . intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function assertAssignmentIsUsable(Course $course, PartnerAssignedCourse $assignment, int $partnerId): void
    {
        if ((int) $assignment->partner_id !== $partnerId) {
            throw new DomainException('This course is assigned to another partner.');
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            throw new DomainException('The partner course assignment does not match the selected course.');
        }

        if ($assignment->status !== 'active') {
            throw new DomainException('This partner course assignment is inactive.');
        }
    }

    private function originalFee(Course $course): string
    {
        $saleMinor = $this->toMinorUnits((string) ($course->sale_price ?? '0'));

        return $saleMinor > 0
            ? $this->fromMinorUnits($saleMinor)
            : (string) ($course->regular_price ?? '0');
    }

    private function planFromAmounts(string $type, string $title, string $paymentMode, array $minorAmounts): array
    {
        $installments = [];
        foreach ($minorAmounts as $index => $minorAmount) {
            $installments[] = [
                'number' => $index + 1,
                'offset_months' => $index,
                'amount_minor' => $minorAmount,
                'amount' => $this->fromMinorUnits($minorAmount),
            ];
        }

        $totalMinor = array_sum($minorAmounts);

        return [
            'type' => $type,
            'title' => $title,
            'payment_mode' => $paymentMode,
            'amount_due_now_minor' => $minorAmounts[0],
            'amount_due_now' => $this->fromMinorUnits($minorAmounts[0]),
            'total_minor' => $totalMinor,
            'total_amount' => $this->fromMinorUnits($totalMinor),
            'number_of_installments' => count($minorAmounts),
            'installment_amounts' => array_column($installments, 'amount'),
            'installments' => $installments,
        ];
    }

    private function toScaledInteger(string $amount, int $scale): int
    {
        $value = trim($amount);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            throw new DomainException('Invalid decimal amount.');
        }

        $sign = $matches[1] === '-' ? -1 : 1;
        $fraction = $matches[3] ?? '';
        $keptFraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);
        $scaled = ((int) $matches[2] * (10 ** $scale)) + (int) $keptFraction;

        $roundingDigit = (int) ($fraction[$scale] ?? 0);
        if ($roundingDigit >= 5) {
            $scaled++;
        }

        return $scaled * $sign;
    }

    private function normaliseDecimal(string $value): string
    {
        return $this->fromMinorUnits($this->toScaledInteger($value, 2));
    }
}
