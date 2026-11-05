<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Crm\GradeExtraAttempt;
use App\Models\Crm\GradeReset;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One learner uploaded the wrong file to an assignment that is not graded yet: let them upload it again.
 * Records a "resubmission allowed" (grade_resets, the same record the assessor's "Allow resubmit" makes) plus one extra
 * attempt (grade_extra_attempts), so the replacement does not use up their normal resubmission. The wrong file stays in
 * the history; the assessor grades the newest attempt. Run with --dry-run first.
 *
 *   php artisan assignments:allow-reupload 242 87 --dry-run
 */
class AllowAssignmentReupload extends Command
{
    protected $signature = 'assignments:allow-reupload {learner : Portal learner id} {assignment : Portal assignment id}
        {--reason=Learner uploaded the wrong file; admissions allowed one replacement upload}
        {--dry-run : Show what would happen, change nothing}';
    protected $description = 'Let one learner upload an assignment again after uploading the wrong file (not graded yet)';

    public function handle(): int
    {
        $learner = User::find((int) $this->argument('learner'));
        $assignment = Assignment::with('course')->find((int) $this->argument('assignment'));
        if (!$learner || !$assignment) {
            $this->error('Learner or assignment not found.');

            return self::FAILURE;
        }

        $submissions = AssignmentSubmission::where('learner_id', $learner->id)->where('assignment_id', $assignment->id)->orderBy('id')->get();
        $this->line("Learner: #{$learner->id} " . trim(($learner->first_name ?? '') . ' ' . ($learner->sur_name ?? '')) . ' <' . ($learner->email_address ?? $learner->email ?? '') . '>');
        $this->line("Assignment: #{$assignment->id} {$assignment->title} (" . ($assignment->course?->title ?? 'course ' . $assignment->course_id) . ')');
        foreach ($submissions as $s) {
            $this->line("  Submission #{$s->id}: attempt {$s->attempt_no}, {$s->file_name}, {$s->created_at}");
        }

        $latest = $submissions->last();
        if (!$latest) {
            $this->error('Nothing uploaded to this assignment yet: the learner can upload normally.');

            return self::FAILURE;
        }
        $graded = \Illuminate\Support\Facades\DB::connection('mysql_crm')->table('grade_sheet_cells')
            ->where('portal_assignment_id', $assignment->id)->where('last_submission_portal_id', $latest->id)->where(fn ($q) => $q->whereNotNull('grade_text')->where('grade_text', '!=', ''))->exists();
        if ($graded) {
            $this->error('The latest upload has already been graded: use the assessor\'s Refer / Allow resubmit in the CRM instead.');

            return self::FAILURE;
        }
        if (GradeReset::where('learner_id', $learner->id)->where('portal_assignment_id', $assignment->id)->where('reset_at', '>', $latest->created_at)->exists()) {
            $this->info('Already allowed: the learner can upload again now.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: would allow one more upload (+1 attempt). Nothing changed.');

            return self::SUCCESS;
        }

        GradeExtraAttempt::create([
            'learner_id' => $learner->id,
            'portal_assignment_id' => $assignment->id,
            'additional_attempts' => 1,
            'reason' => $this->option('reason'),
            'granted_at' => now(),
        ]);
        GradeReset::create([
            'learner_id' => $learner->id,
            'portal_assignment_id' => $assignment->id,
            'reason' => $this->option('reason'),
            'reset_at' => now(),
        ]);

        $this->info('Done: the learner can upload this assignment again now (the upload form is back on their course page).');

        return self::SUCCESS;
    }
}
