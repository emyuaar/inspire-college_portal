<?php

namespace App\Services\Lms;

use App\Models\CourseModule;
use App\Models\LearnerCourseUnitSelection;
use App\Models\Website\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditCompletionService
{
    public function evaluate(int $learnerId, Course $course): array
    {
        if (!$course->usesCreditBasedCompletion()) {
            return ['mode' => 'standard', 'eligible' => null, 'missing_requirements' => []];
        }
        if (!$course->hasConfiguredCreditSetup()) {
            return [
                'mode' => 'credit_based',
                'setup_status' => $course->credit_setup_status ?: 'pending_setup',
                'eligible' => false,
                'required_credits' => $course->total_required_credits === null ? null : (float) $course->total_required_credits,
                'achieved_credits' => 0.0,
                'achieved_mandatory_credits' => 0.0,
                'achieved_optional_credits' => 0.0,
                'pending_credits' => $course->total_required_credits === null ? null : (float) $course->total_required_credits,
                'units' => collect(),
                'passed_units' => collect(),
                'referred_or_failed_units' => collect(),
                'selection_locked' => false,
                'missing_requirements' => ['Credit setup is not fully configured yet.'],
            ];
        }

        $selections = LearnerCourseUnitSelection::with('module.optionalGroup')
            ->where('learner_id', $learnerId)
            ->where('course_id', $course->id)
            ->get();

        $mandatory = CourseModule::with('assignments')
            ->where('course_id', $course->id)
            ->where('section_type', 'unit')
            ->where('unit_type', 'mandatory')
            ->where('included_in_completion', true)
            ->where('status', 'active')
            ->get();
        $selectedOptionalIds = $selections->where('unit_type', 'optional')->pluck('module_id');
        $optional = CourseModule::with('assignments')
            ->whereIn('id', $selectedOptionalIds)
            ->where('section_type', 'unit')
            ->where('unit_type', 'optional')
            ->where('included_in_completion', true)
            ->where('status', 'active')
            ->get();
        $units = $mandatory->concat($optional);
        $passMap = $this->assignmentPassMap($learnerId, $units->pluck('assignments')->flatten()->pluck('id'));

        $unitRows = $units->map(function (CourseModule $module) use ($passMap) {
            $assignmentIds = $module->assignments->pluck('id');
            $passed = $assignmentIds->isNotEmpty()
                && $assignmentIds->every(fn ($id) => (bool) ($passMap[$id] ?? false));
            return [
                'id' => $module->id,
                'title' => $module->unit_title ?: $module->title,
                'unit_code' => $module->unit_code,
                'unit_type' => $module->unit_type,
                'credits' => $module->credits === null ? null : (float) $module->credits,
                'passed' => $passed,
                'assignment_count' => $assignmentIds->count(),
            ];
        });

        $passed = $unitRows->where('passed', true);
        $achievedMandatory = $passed->where('unit_type', 'mandatory')->sum('credits');
        $achievedOptional = $passed->where('unit_type', 'optional')->sum('credits');
        $achieved = $achievedMandatory + $achievedOptional;
        $required = (float) $course->total_required_credits;
        $missing = [];

        if ($unitRows->where('unit_type', 'mandatory')->contains('passed', false)) {
            $missing[] = 'All mandatory units must be passed.';
        }
        if ($unitRows->where('unit_type', 'optional')->contains('passed', false)) {
            $missing[] = 'All selected optional units must be passed.';
        }
        if ($achieved < $required) {
            $missing[] = number_format($required - $achieved, 2) . ' more achieved credits are required.';
        }
        if ($unitRows->contains(fn ($unit) => $unit['assignment_count'] === 0)) {
            $missing[] = 'Each selected completion unit must contain at least one assessed assignment.';
        }

        $selectionErrors = app(UnitSelectionService::class)->selectionErrors($course, $units);
        $missing = array_values(array_unique(array_merge($missing, $selectionErrors)));

        return [
            'mode' => 'credit_based',
            'eligible' => !$missing,
            'required_credits' => $required,
            'achieved_credits' => $achieved,
            'achieved_mandatory_credits' => $achievedMandatory,
            'achieved_optional_credits' => $achievedOptional,
            'pending_credits' => max(0, $required - $achieved),
            'units' => $unitRows->values(),
            'passed_units' => $unitRows->where('passed', true)->values(),
            'referred_or_failed_units' => $unitRows->where('passed', false)->values(),
            'selection_locked' => $selections->where('unit_type', 'optional')->contains('is_locked', true),
            'missing_requirements' => $missing,
        ];
    }

    private function assignmentPassMap(int $learnerId, Collection $assignmentIds): array
    {
        if ($assignmentIds->isEmpty()) return [];

        $finalSubmissionIds = DB::connection('mysql_portal')
            ->table('assignment_submissions as s')
            ->join('assignment_submission_statuses as ss', 'ss.id', '=', 's.status_id')
            ->where('s.learner_id', $learnerId)
            ->whereIn('s.assignment_id', $assignmentIds)
            ->whereIn('ss.name', ['graded', 'iqa_approved'])
            ->pluck('s.id');
        if ($finalSubmissionIds->isEmpty()) return [];

        return DB::connection('mysql_crm')
            ->table('grade_attempts as ga')
            ->join('grade_sheet_cells as gc', 'gc.id', '=', 'ga.grade_sheet_cell_id')
            ->join('grade_sheet_rows as gr', 'gr.id', '=', 'gc.grade_sheet_row_id')
            ->leftJoin('grading_scale_items as gsi', 'gsi.id', '=', 'ga.grade_scale_item_id')
            ->where('gr.learner_id', $learnerId)
            ->whereIn('ga.portal_submission_id', $finalSubmissionIds)
            ->whereIn('gc.portal_assignment_id', $assignmentIds)
            ->orderByDesc('ga.graded_at')
            ->orderByDesc('ga.id')
            ->get(['gc.portal_assignment_id', 'ga.result', 'gsi.is_pass'])
            ->unique('portal_assignment_id')
            ->mapWithKeys(fn ($row) => [
                $row->portal_assignment_id => (bool) $row->is_pass
                    || in_array(strtolower((string) $row->result), ['pass', 'merit', 'distinction'], true),
            ])
            ->all();
    }
}
