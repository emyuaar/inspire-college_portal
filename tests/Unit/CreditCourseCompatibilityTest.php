<?php

namespace Tests\Unit;

use App\Models\LearnerCourseUnitSelection;
use App\Models\CourseModule;
use App\Models\Website\Course;
use App\Services\Lms\UnitSelectionService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CreditCourseCompatibilityTest extends TestCase
{
    public function test_standard_mode_never_uses_credit_completion_even_if_boolean_is_true(): void
    {
        $course = new Course([
            'completion_mode' => 'standard',
            'uses_credit_based_completion' => true,
            'total_required_credits' => null,
        ]);

        $this->assertFalse($course->usesCreditBasedCompletion());
        $this->assertNull($course->total_required_credits);
    }

    public function test_credit_summary_preserves_null_required_credits(): void
    {
        $course = new Course([
            'completion_mode' => 'credit_based',
            'uses_credit_based_completion' => true,
            'total_required_credits' => null,
        ]);
        $selections = new Collection([
            new LearnerCourseUnitSelection(['unit_type' => 'mandatory', 'credits_at_selection' => 20]),
        ]);

        $summary = (new UnitSelectionService())->summary($course, $selections);

        $this->assertNull($summary['required_credits']);
        $this->assertNull($summary['remaining_credits']);
        $this->assertSame(20.0, $summary['total_selected_credits']);
    }

    public function test_guidelines_section_is_not_a_unit(): void
    {
        $guidelines = new CourseModule(['section_type' => 'guidelines', 'unit_type' => null]);
        $unit = new CourseModule(['section_type' => 'unit', 'unit_type' => 'mandatory']);

        $this->assertFalse($guidelines->isUnit());
        $this->assertSame('Guidelines', $guidelines->section_type_label);
        $this->assertTrue($unit->isUnit());
    }

    public function test_credit_completion_is_only_enforced_when_setup_is_configured(): void
    {
        $pending = new Course([
            'completion_mode' => 'credit_based',
            'uses_credit_based_completion' => true,
            'credit_setup_status' => 'pending_setup',
        ]);
        $configured = new Course([
            'completion_mode' => 'credit_based',
            'uses_credit_based_completion' => true,
            'credit_setup_status' => 'configured',
        ]);

        $this->assertTrue($pending->usesCreditBasedCompletion());
        $this->assertFalse($pending->hasConfiguredCreditSetup());
        $this->assertTrue($configured->hasConfiguredCreditSetup());
    }
}
