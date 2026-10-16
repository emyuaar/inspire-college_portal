<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live learner_diagnostic_logs was created by the smaller 2026_06_09 migration, but LearnerLogger writes the full
 * 2026_04_21 column set (module_id, assignment_id, browser, ...), so every insert has failed since 31 Jul 2026
 * ("Unknown column 'module_id'") and the CRM's learner diagnostic logs stopped updating.
 * Adds only the columns that are missing; existing rows and columns are untouched. Safe to run more than once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learner_diagnostic_logs')) {
            return;
        }

        $columns = [
            'course_name' => fn (Blueprint $t) => $t->string('course_name')->nullable(),
            'module_id' => fn (Blueprint $t) => $t->unsignedBigInteger('module_id')->nullable()->index(),
            'module_name' => fn (Blueprint $t) => $t->string('module_name')->nullable(),
            'assignment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('assignment_id')->nullable()->index(),
            'controller_action' => fn (Blueprint $t) => $t->string('controller_action')->nullable(),
            'request_type' => fn (Blueprint $t) => $t->string('request_type', 50)->nullable()->index(),
            'browser' => fn (Blueprint $t) => $t->string('browser', 100)->nullable(),
            'device_type' => fn (Blueprint $t) => $t->string('device_type', 50)->nullable(),
            'os' => fn (Blueprint $t) => $t->string('os', 100)->nullable(),
            'correlation_id' => fn (Blueprint $t) => $t->string('correlation_id')->nullable()->index(),
            'timezone' => fn (Blueprint $t) => $t->string('timezone', 100)->nullable(),
            'action_attempted' => fn (Blueprint $t) => $t->string('action_attempted')->nullable(),
            'message_technical' => fn (Blueprint $t) => $t->longText('message_technical')->nullable(),
            'exception_class' => fn (Blueprint $t) => $t->string('exception_class')->nullable(),
            'exception_message' => fn (Blueprint $t) => $t->longText('exception_message')->nullable(),
            'file_name' => fn (Blueprint $t) => $t->string('file_name')->nullable(),
            'mime_type' => fn (Blueprint $t) => $t->string('mime_type')->nullable(),
            'file_size' => fn (Blueprint $t) => $t->unsignedBigInteger('file_size')->nullable(),
            'response_status_code' => fn (Blueprint $t) => $t->integer('response_status_code')->nullable(),
            'response_payload_summary' => fn (Blueprint $t) => $t->longText('response_payload_summary')->nullable(),
            'validation_errors' => fn (Blueprint $t) => $t->json('validation_errors')->nullable(),
            'stack_trace' => fn (Blueprint $t) => $t->longText('stack_trace')->nullable(),
            'frontend_error_stack' => fn (Blueprint $t) => $t->longText('frontend_error_stack')->nullable(),
            'referrer' => fn (Blueprint $t) => $t->text('referrer')->nullable(),
            'learner_initiated' => fn (Blueprint $t) => $t->boolean('learner_initiated')->default(true),
            'blocked_by_permission' => fn (Blueprint $t) => $t->boolean('blocked_by_permission')->default(false),
            'is_reproducible' => fn (Blueprint $t) => $t->boolean('is_reproducible')->nullable(),
            'retry_happened' => fn (Blueprint $t) => $t->boolean('retry_happened')->default(false),
            'retry_count' => fn (Blueprint $t) => $t->integer('retry_count')->default(0),
            'elapsed_time_ms' => fn (Blueprint $t) => $t->decimal('elapsed_time_ms', 10, 2)->nullable(),
            'upload_duration_ms' => fn (Blueprint $t) => $t->decimal('upload_duration_ms', 10, 2)->nullable(),
            'network_state' => fn (Blueprint $t) => $t->string('network_state')->nullable(),
            'page_state_component' => fn (Blueprint $t) => $t->string('page_state_component')->nullable(),
            'additional_context' => fn (Blueprint $t) => $t->json('additional_context')->nullable(),
        ];

        foreach ($columns as $name => $add) {
            if (!Schema::hasColumn('learner_diagnostic_logs', $name)) {
                Schema::table('learner_diagnostic_logs', fn (Blueprint $t) => $add($t));
            }
        }
    }

    public function down(): void
    {
        // Not reversed: the columns are nullable/defaulted and removing them would break logging again.
    }
};
