<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('learner_diagnostic_logs', function (Blueprint $table) {
            $table->id();
            
            // Core identity
            $table->unsignedBigInteger('learner_id')->nullable()->index();
            $table->string('learner_name')->nullable();
            $table->string('learner_email')->nullable()->index();
            $table->unsignedBigInteger('portal_user_id')->nullable()->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('course_name')->nullable();
            $table->unsignedBigInteger('module_id')->nullable()->index();
            $table->string('module_name')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable()->index();
            $table->unsignedBigInteger('assignment_id')->nullable()->index();
            
            // Request / environment
            $table->text('url')->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('controller_action')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('request_type', 50)->nullable()->index(); // page / ajax / api / upload / background
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('session_id')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->string('timezone', 100)->nullable();
            
            // Action metadata
            $table->string('event_type')->index();
            $table->string('event_category')->index(); 
            $table->string('action_attempted')->nullable();
            $table->string('action_status')->index(); // started / success / failed / warning / blocked
            $table->string('severity')->index(); // info / warning / error / critical
            $table->text('message_human')->nullable();
            $table->longText('message_technical')->nullable();
            $table->string('exception_class')->nullable();
            $table->longText('exception_message')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('response_status_code')->nullable();
            $table->longText('response_payload_summary')->nullable();
            $table->json('validation_errors')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->longText('frontend_error_stack')->nullable();
            $table->text('referrer')->nullable();
            
            // Context
            $table->boolean('learner_initiated')->default(true);
            $table->boolean('blocked_by_permission')->default(false);
            $table->boolean('is_reproducible')->nullable();
            $table->boolean('retry_happened')->default(false);
            $table->integer('retry_count')->default(0);
            $table->decimal('elapsed_time_ms', 10, 2)->nullable();
            $table->decimal('upload_duration_ms', 10, 2)->nullable();
            $table->string('network_state')->nullable();
            $table->string('page_state_component')->nullable();
            $table->json('additional_context')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_diagnostic_logs');
    }
};
