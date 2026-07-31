<?php

namespace App\Services\Lms;

use App\Models\CourseModule;
use App\Models\LearnerCourseUnitSelection;
use App\Models\LearnerCourseUnitSelectionHistory;
use App\Models\Website\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnitSelectionService
{
    public function ensureMandatorySelections(int $learnerId, Course $course): void
    {
        if (!$course->hasConfiguredCreditSetup()) {
            return;
        }

        CourseModule::where('course_id', $course->id)
            ->where('section_type', 'unit')
            ->where('unit_type', 'mandatory')
            ->where('included_in_completion', true)
            ->where('status', 'active')
            ->get()
            ->each(function (CourseModule $module) use ($learnerId, $course) {
                $selection = LearnerCourseUnitSelection::firstOrCreate(
                    ['learner_id' => $learnerId, 'course_id' => $course->id, 'module_id' => $module->id],
                    [
                        'unit_type' => 'mandatory',
                        'credits_at_selection' => $module->credits,
                        'is_locked' => true,
                        'selected_by' => 'system',
                        'selected_at' => now(),
                        'locked_at' => now(),
                    ]
                );

                if ($selection->wasRecentlyCreated) {
                    $this->history($selection, 'selected', null, $selection->toArray(), null, 'Mandatory unit automatically selected.');
                    $this->history($selection, 'locked', null, ['is_locked' => true], null, 'Mandatory units are locked.');
                }
            });
    }

    public function selectOptional(int $learnerId, Course $course, CourseModule $module, string $selectedBy = 'learner', ?int $changedBy = null, ?string $reason = null): LearnerCourseUnitSelection
    {
        $this->assertSelectableCourseAndUnit($course, $module);

        return DB::connection('mysql_portal')->transaction(function () use ($learnerId, $course, $module, $selectedBy, $changedBy, $reason) {
            $existing = LearnerCourseUnitSelection::where([
                'learner_id' => $learnerId,
                'course_id' => $course->id,
                'module_id' => $module->id,
            ])->first();
            if ($existing) {
                return $existing;
            }

            $current = LearnerCourseUnitSelection::with('module')
                ->where('learner_id', $learnerId)
                ->where('course_id', $course->id)
                ->where('unit_type', 'optional')
                ->get();
            $projected = $current->pluck('module')->filter()->push($module);
            $this->validateMaximumRules($course, $projected);

            $selection = LearnerCourseUnitSelection::create([
                'learner_id' => $learnerId,
                'course_id' => $course->id,
                'module_id' => $module->id,
                'unit_type' => 'optional',
                'credits_at_selection' => $module->credits,
                'is_locked' => false,
                'selected_by' => $selectedBy,
                'selected_at' => now(),
            ]);

            $this->history($selection, $selectedBy === 'admin' ? 'admin_override' : 'selected', null, $selection->toArray(), $changedBy, $reason);
            return $selection;
        });
    }

    public function removeOptional(int $learnerId, Course $course, CourseModule $module, string $selectedBy = 'learner', ?int $changedBy = null, ?string $reason = null): void
    {
        $selection = LearnerCourseUnitSelection::where([
            'learner_id' => $learnerId,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'unit_type' => 'optional',
        ])->firstOrFail();

        $this->lockWhenWorkExists($selection);
        if ($selection->fresh()->is_locked) {
            throw ValidationException::withMessages(['module_id' => 'This optional unit is locked because work has already been submitted.']);
        }

        $old = $selection->toArray();
        $selection->delete();
        $this->history($selection, $selectedBy === 'admin' ? 'admin_override' : 'removed', $old, null, $changedBy, $reason);
    }

    public function finalise(int $learnerId, Course $course): array
    {
        $this->ensureMandatorySelections($learnerId, $course);
        $selected = LearnerCourseUnitSelection::with('module.optionalGroup')
            ->where('learner_id', $learnerId)
            ->where('course_id', $course->id)
            ->get();

        $errors = $this->selectionErrors($course, $selected->pluck('module')->filter());
        if ($errors) {
            throw ValidationException::withMessages(['selection' => $errors]);
        }

        return $this->summary($course, $selected);
    }

    public function refreshLocks(int $learnerId, int $courseId): void
    {
        LearnerCourseUnitSelection::where('learner_id', $learnerId)
            ->where('course_id', $courseId)
            ->where('unit_type', 'optional')
            ->where('is_locked', false)
            ->get()
            ->each(fn ($selection) => $this->lockWhenWorkExists($selection));
    }

    public function summary(Course $course, $selections): array
    {
        $mandatory = $selections->where('unit_type', 'mandatory');
        $optional = $selections->where('unit_type', 'optional');
        $mandatoryCredits = $mandatory->sum(fn ($item) => (float) ($item->credits_at_selection ?? 0));
        $optionalCredits = $optional->sum(fn ($item) => (float) ($item->credits_at_selection ?? 0));
        $required = $course->total_required_credits === null ? null : (float) $course->total_required_credits;
        $total = $mandatoryCredits + $optionalCredits;

        return [
            'mandatory_credits' => $mandatoryCredits,
            'optional_credits' => $optionalCredits,
            'total_selected_credits' => $total,
            'required_credits' => $required,
            'remaining_credits' => $required === null ? null : max(0, $required - $total),
            'selected_optional_units' => $optional->count(),
            'selection_locked' => $optional->contains('is_locked', true),
        ];
    }

    public function selectionErrors(Course $course, $modules): array
    {
        $errors = [];
        $mandatory = $modules->where('unit_type', 'mandatory');
        $optional = $modules->where('unit_type', 'optional');
        $total = $modules->sum(fn ($module) => (float) ($module->credits ?? 0));

        if ($course->total_required_credits !== null && $total < (float) $course->total_required_credits) {
            $errors[] = 'Select enough optional units to meet the required course credits.';
        }
        if ($course->minimum_optional_units !== null && $optional->count() < (int) $course->minimum_optional_units) {
            $errors[] = "Select at least {$course->minimum_optional_units} optional units.";
        }
        if ($course->maximum_optional_units !== null && $optional->count() > (int) $course->maximum_optional_units) {
            $errors[] = "Select no more than {$course->maximum_optional_units} optional units.";
        }
        if ($course->optional_credits_required !== null && $optional->sum('credits') < (float) $course->optional_credits_required) {
            $errors[] = 'Selected optional credits are below the course requirement.';
        }
        if ($course->mandatory_credits_required !== null && $mandatory->sum('credits') < (float) $course->mandatory_credits_required) {
            $errors[] = 'Selected mandatory credits are below the course requirement.';
        }

        $groups = $optional->filter(fn ($module) => $module->optionalGroup)->groupBy('optional_group_id');
        foreach (\App\Models\CourseOptionalGroup::where('course_id', $course->id)->get() as $group) {
            $selected = $groups->get($group->id, collect());
            $credits = $selected->sum('credits');
            if ($group->minimum_units !== null && $selected->count() < $group->minimum_units) $errors[] = "{$group->name}: select at least {$group->minimum_units} units.";
            if ($group->maximum_units !== null && $selected->count() > $group->maximum_units) $errors[] = "{$group->name}: select no more than {$group->maximum_units} units.";
            if ($group->minimum_credits !== null && $credits < (float) $group->minimum_credits) $errors[] = "{$group->name}: select at least {$group->minimum_credits} credits.";
            if ($group->maximum_credits !== null && $credits > (float) $group->maximum_credits) $errors[] = "{$group->name}: selected credits exceed {$group->maximum_credits}.";
        }

        return array_values(array_unique($errors));
    }

    private function validateMaximumRules(Course $course, $projected): void
    {
        $optional = $projected->where('unit_type', 'optional');
        $messages = [];
        if ($course->maximum_optional_units !== null && $optional->count() > (int) $course->maximum_optional_units) {
            $messages[] = "You may select no more than {$course->maximum_optional_units} optional units.";
        }
        foreach ($optional->filter(fn ($m) => $m->optionalGroup)->groupBy('optional_group_id') as $modules) {
            $group = $modules->first()->optionalGroup;
            if ($group->maximum_units !== null && $modules->count() > $group->maximum_units) $messages[] = "{$group->name} allows no more than {$group->maximum_units} units.";
            if ($group->maximum_credits !== null && $modules->sum('credits') > (float) $group->maximum_credits) $messages[] = "{$group->name} allows no more than {$group->maximum_credits} credits.";
        }
        if ($messages) throw ValidationException::withMessages(['module_id' => $messages]);
    }

    private function lockWhenWorkExists(LearnerCourseUnitSelection $selection): void
    {
        if ($selection->is_locked) return;
        $assignmentIds = CourseModule::find($selection->module_id)?->assignments()->pluck('id') ?? collect();
        if ($assignmentIds->isNotEmpty() && DB::connection('mysql_portal')->table('assignment_submissions')
            ->where('learner_id', $selection->learner_id)->whereIn('assignment_id', $assignmentIds)->exists()) {
            $old = $selection->toArray();
            $selection->update(['is_locked' => true, 'locked_at' => now()]);
            $this->history($selection, 'locked', $old, $selection->fresh()->toArray(), null, 'Assignment work exists for this unit.');
        }
    }

    private function assertSelectableCourseAndUnit(Course $course, CourseModule $module): void
    {
        if (!$course->hasConfiguredCreditSetup()
            || (int) $module->course_id !== (int) $course->id
            || !$module->isUnit()
            || $module->unit_type !== 'optional'
            || !$module->included_in_completion
            || $module->status !== 'active') {
            throw ValidationException::withMessages(['module_id' => 'This unit is not available for optional selection.']);
        }
    }

    private function history(LearnerCourseUnitSelection $selection, string $action, ?array $old, ?array $new, ?int $changedBy, ?string $reason): void
    {
        LearnerCourseUnitSelectionHistory::create([
            'learner_id' => $selection->learner_id,
            'course_id' => $selection->course_id,
            'module_id' => $selection->module_id,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
