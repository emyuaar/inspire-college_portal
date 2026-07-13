<?php

namespace Tests\Unit;

use App\Models\Crm\PartnerAssignedCourse;
use App\Models\Website\Course;
use App\Services\PartnerCoursePricingService;
use App\Services\PricingService;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartnerCoursePricingServiceTest extends TestCase
{
    private PartnerCoursePricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PartnerCoursePricingService::class);
    }

    #[Test]
    public function six_hundred_pounds_with_twenty_percent_discount_gives_four_hundred_and_eighty(): void
    {
        $quote = $this->service->quote($this->course('600.00'), $this->assignment('percentage', '20.00'), 10);

        $this->assertSame('120.00', $quote['partner_discount_amount']);
        $this->assertSame('480.00', $quote['final_course_fee']);
    }

    #[Test]
    public function six_hundred_pounds_with_fixed_eighty_pound_discount_gives_five_hundred_and_twenty(): void
    {
        $quote = $this->service->quote($this->course('600.00'), $this->assignment('fixed', '80.00'), 10);

        $this->assertSame('80.00', $quote['partner_discount_amount']);
        $this->assertSame('520.00', $quote['final_course_fee']);
    }

    #[Test]
    public function full_payment_charges_the_complete_discounted_fee(): void
    {
        $assignment = $this->assignment('percentage', '20.00');
        $quote = $this->service->quote($this->course('600.00'), $assignment, 10);
        $plan = $this->service->buildPlan($assignment, 'full', $quote);

        $this->assertSame('480.00', $plan['amount_due_now']);
        $this->assertSame('480.00', $plan['total_amount']);
        $this->assertSame(['480.00'], $plan['installment_amounts']);
    }

    #[Test]
    public function two_month_plan_totals_the_exact_discounted_fee(): void
    {
        $assignment = $this->assignment('percentage', '20.00');
        $quote = $this->service->quote($this->course('600.00'), $assignment, 10);
        $plan = $this->service->buildPlan($assignment, 'two_months', $quote);

        $this->assertSame(['240.00', '240.00'], $plan['installment_amounts']);
        $this->assertSame(48000, array_sum(array_column($plan['installments'], 'amount_minor')));
    }

    #[Test]
    public function three_month_plan_totals_the_exact_discounted_fee(): void
    {
        $assignment = $this->assignment('percentage', '20.00');
        $quote = $this->service->quote($this->course('600.00'), $assignment, 10);
        $plan = $this->service->buildPlan($assignment, 'three_months', $quote);

        $this->assertSame(['160.00', '160.00', '160.00'], $plan['installment_amounts']);
        $this->assertSame(48000, array_sum(array_column($plan['installments'], 'amount_minor')));
    }

    #[Test]
    public function rounding_remainder_is_reconciled_in_the_final_schedule_total(): void
    {
        $assignment = $this->assignment('percentage', '0.00');
        $quote = $this->service->quote($this->course('500.00'), $assignment, 10);
        $plan = $this->service->buildPlan($assignment, 'three_months', $quote);

        $this->assertSame(['166.67', '166.67', '166.66'], $plan['installment_amounts']);
        $this->assertSame(50000, array_sum(array_column($plan['installments'], 'amount_minor')));
    }

    #[Test]
    public function disabled_payment_option_cannot_be_selected_by_a_tampered_request(): void
    {
        $assignment = $this->assignment('percentage', '20.00', ['allow_two_months' => false]);
        $quote = $this->service->quote($this->course('600.00'), $assignment, 10);

        $this->expectException(DomainException::class);
        $this->service->buildPlan($assignment, 'two_months', $quote);
    }

    #[Test]
    public function partner_cannot_use_a_course_assignment_owned_by_another_partner(): void
    {
        $this->expectException(DomainException::class);
        $this->service->quote($this->course('600.00'), $this->assignment('percentage', '20.00'), 99);
    }

    #[Test]
    public function inactive_partner_course_assignment_cannot_be_used(): void
    {
        $assignment = $this->assignment('percentage', '20.00', ['status' => 'disabled']);

        $this->expectException(DomainException::class);
        $this->service->quote($this->course('600.00'), $assignment, 10);
    }

    #[Test]
    public function percentage_discount_above_one_hundred_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->service->quote($this->course('600.00'), $this->assignment('percentage', '100.01'), 10);
    }

    #[Test]
    public function fixed_discount_above_the_course_fee_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->service->quote($this->course('600.00'), $this->assignment('fixed', '600.01'), 10);
    }

    #[Test]
    public function normal_non_partner_pricing_continues_to_use_the_normal_course_price(): void
    {
        $course = $this->course('600.00');
        $course->setRelation('activePromotion', null);
        $course->setRelation('activeCoursePromotion', null);

        $pricing = app(PricingService::class)->getCoursePricing($course);

        $this->assertSame(600.0, $pricing['final_full_price']);
    }

    #[Test]
    public function captured_price_snapshot_values_do_not_change_after_course_or_assignment_edits(): void
    {
        $course = $this->course('600.00');
        $assignment = $this->assignment('percentage', '20.00');
        $snapshot = $this->service->quote($course, $assignment, 10);

        $course->regular_price = '900.00';
        $assignment->discount_value = '50.00';

        $this->assertSame('600.00', $snapshot['original_course_fee']);
        $this->assertSame('20.00', $snapshot['partner_discount_value']);
        $this->assertSame('480.00', $snapshot['final_course_fee']);
    }

    #[Test]
    public function checkout_minor_amount_matches_the_final_amount_saved_for_full_payment(): void
    {
        $assignment = $this->assignment('percentage', '20.00');
        $quote = $this->service->quote($this->course('600.00'), $assignment, 10);
        $plan = $this->service->buildPlan($assignment, 'full', $quote);

        $savedFinalMinor = $this->service->toMinorUnits($quote['final_course_fee']);
        $checkoutMinor = $this->service->toMinorUnits($plan['amount_due_now']);

        $this->assertSame(48000, $savedFinalMinor);
        $this->assertSame($savedFinalMinor, $checkoutMinor);
    }

    #[Test]
    public function sale_price_is_used_as_the_existing_effective_course_fee_when_present(): void
    {
        $course = $this->course('1000.00');
        $course->sale_price = '900.00';

        $quote = $this->service->quote($course, $this->assignment('percentage', '10.00'), 10);

        $this->assertSame('900.00', $quote['original_course_fee']);
        $this->assertSame('810.00', $quote['final_course_fee']);
    }

    private function course(string $regularPrice): Course
    {
        $course = new Course();
        $course->forceFill([
            'id' => 20,
            'title' => 'Test Course',
            'regular_price' => $regularPrice,
            'sale_price' => '0.00',
            'deposit' => '0.00',
            'monthly_installment' => '0.00',
            'number_of_months' => 0,
        ]);

        return $course;
    }

    private function assignment(string $discountType, string $discountValue, array $overrides = []): PartnerAssignedCourse
    {
        $assignment = new PartnerAssignedCourse();
        $assignment->forceFill(array_merge([
            'id' => 30,
            'partner_id' => 10,
            'course_id' => 20,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'allow_full_payment' => true,
            'allow_two_months' => true,
            'allow_three_months' => true,
            'allow_installments' => false,
            'status' => 'active',
        ], $overrides));

        return $assignment;
    }
}
