<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [F-9] Reconcile the assignment_submissions status schema so BOTH apps work.
 *
 * Background: the CRM reads submissions via `status_id` + an `assignment_submission_statuses`
 * table (AssignmentSubmissionController join, AssignmentSubmission::status() relation), but the
 * live portal DB only had the `status` enum — so the portal could not insert submissions and the
 * CRM could not list them. The CRM's original refactor migrations never applied to this DB.
 *
 * This migration brings the portal DB in line with what the CRM code expects, WITHOUT dropping
 * the `status` column the portal still uses. The portal writes both (see LearnerCourseController).
 * Idempotent and safe to run on environments that were patched manually.
 *
 * NOTE for the team: pick ONE source of truth long-term (status enum vs status_id FK) and make
 * both apps agree; until then both columns are kept in sync.
 */
return new class extends Migration
{
    private string $conn = 'mysql_portal';

    public function up(): void
    {
        $schema = Schema::connection($this->conn);

        if (! $schema->hasTable('assignment_submission_statuses')) {
            $schema->create('assignment_submission_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label');
                $table->string('color_class')->nullable();
                $table->timestamps();
            });
        }

        $statuses = [
            ['name' => 'submitted',          'label' => 'Submitted',             'color_class' => 'badge-ds-submitted'],
            ['name' => 'graded',             'label' => 'Graded',                'color_class' => 'badge-ds-passed'],
            ['name' => 'graded_pending_iqa', 'label' => 'Graded - Pending IQA',  'color_class' => 'badge-ds-passed'],
            ['name' => 'iqa_sampled',        'label' => 'Graded - Sampled',      'color_class' => 'badge-ds-passed'],
            ['name' => 'iqa_approved',       'label' => 'Graded - Approved',     'color_class' => 'badge-ds-passed'],
            ['name' => 'iqa_rejected',       'label' => 'Graded - Rejected',     'color_class' => 'badge-ds-referred'],
            ['name' => 'resubmitted',        'label' => 'Resubmitted',           'color_class' => 'badge-ds-referred'],
            ['name' => 'iqa_returned',       'label' => 'Returned by IQA',       'color_class' => 'badge-ds-referred'],
        ];
        foreach ($statuses as $s) {
            DB::connection($this->conn)->table('assignment_submission_statuses')->updateOrInsert(
                ['name' => $s['name']],
                $s + ['updated_at' => now(), 'created_at' => now()]
            );
        }

        if (! $schema->hasColumn('assignment_submissions', 'status_id')) {
            $schema->table('assignment_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('status_id')->nullable()->after('assignment_id');
            });
        }

        // Backfill status_id from the status enum (graded -> graded_pending_iqa, matching CRM intent).
        $map = DB::connection($this->conn)->table('assignment_submission_statuses')->pluck('id', 'name');
        foreach (DB::connection($this->conn)->table('assignment_submissions')->whereNull('status_id')->get() as $sub) {
            $name = $sub->status === 'graded' ? 'graded_pending_iqa' : $sub->status;
            if (isset($map[$name])) {
                DB::connection($this->conn)->table('assignment_submissions')
                    ->where('id', $sub->id)->update(['status_id' => $map[$name]]);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->conn);
        if ($schema->hasColumn('assignment_submissions', 'status_id')) {
            $schema->table('assignment_submissions', function (Blueprint $table) {
                $table->dropColumn('status_id');
            });
        }
        $schema->dropIfExists('assignment_submission_statuses');
    }
};
