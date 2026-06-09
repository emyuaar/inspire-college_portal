<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learner_diagnostic_logs')) {
            return;
        }

        Schema::create('learner_diagnostic_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learner_id')->nullable()->index();
            $table->string('learner_name')->nullable();
            $table->string('learner_email')->nullable()->index();
            $table->unsignedBigInteger('portal_user_id')->nullable()->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('lesson_id')->nullable()->index();
            $table->text('url')->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('http_method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('event_type')->index();
            $table->string('event_category')->index();
            $table->string('action_status')->index();
            $table->string('severity')->index();
            $table->text('message_human')->nullable();
            $table->json('additional_context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_diagnostic_logs');
    }
};
