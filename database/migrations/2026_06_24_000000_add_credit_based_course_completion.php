<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $website = Schema::connection('mysql_website');
        $portal = Schema::connection('mysql_portal');

        $website->table('courses', function (Blueprint $table) use ($website) {
            if (!$website->hasColumn('courses', 'completion_mode')) $table->string('completion_mode', 30)->default('standard');
            if (!$website->hasColumn('courses', 'uses_credit_based_completion')) $table->boolean('uses_credit_based_completion')->default(false);
            foreach (['total_required_credits', 'mandatory_credits_required', 'optional_credits_required'] as $column) {
                if (!$website->hasColumn('courses', $column)) $table->decimal($column, 8, 2)->nullable();
            }
            foreach (['minimum_optional_units', 'maximum_optional_units'] as $column) {
                if (!$website->hasColumn('courses', $column)) $table->unsignedInteger($column)->nullable();
            }
            if (!$website->hasColumn('courses', 'completion_rule_type')) $table->string('completion_rule_type', 80)->nullable();
        });

        DB::connection('mysql_website')->table('courses')->whereNull('completion_mode')
            ->update(['completion_mode' => 'standard', 'uses_credit_based_completion' => false]);

        if (!$portal->hasTable('course_optional_groups')) {
            $portal->create('course_optional_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('minimum_units')->nullable();
                $table->unsignedInteger('maximum_units')->nullable();
                $table->decimal('minimum_credits', 8, 2)->nullable();
                $table->decimal('maximum_credits', 8, 2)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['course_id', 'name']);
            });
        }

        $portal->table('lms_course_modules', function (Blueprint $table) use ($portal) {
            if (!$portal->hasColumn('lms_course_modules', 'unit_code')) $table->string('unit_code')->nullable();
            if (!$portal->hasColumn('lms_course_modules', 'unit_title')) $table->string('unit_title')->nullable();
            foreach (['credits', 'glh', 'tqt'] as $column) {
                if (!$portal->hasColumn('lms_course_modules', $column)) $table->decimal($column, 8, 2)->nullable();
            }
            if (!$portal->hasColumn('lms_course_modules', 'unit_type')) $table->string('unit_type', 20)->default('mandatory');
            if (!$portal->hasColumn('lms_course_modules', 'optional_group_id')) $table->unsignedBigInteger('optional_group_id')->nullable()->index();
            if (!$portal->hasColumn('lms_course_modules', 'included_in_completion')) $table->boolean('included_in_completion')->default(true);
            if (!$portal->hasColumn('lms_course_modules', 'status')) $table->string('status', 20)->default('active');
        });

        DB::connection('mysql_portal')->table('lms_course_modules')->whereNull('unit_type')->update(['unit_type' => 'mandatory']);
        DB::connection('mysql_portal')->table('lms_course_modules')->whereNull('included_in_completion')->update(['included_in_completion' => true]);

        if (!$portal->hasTable('learner_course_unit_selections')) {
            $portal->create('learner_course_unit_selections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('learner_id')->index();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('module_id')->index();
                $table->string('unit_type', 20);
                $table->decimal('credits_at_selection', 8, 2)->nullable();
                $table->boolean('is_locked')->default(false);
                $table->string('selected_by', 20)->default('learner');
                $table->timestamp('selected_at')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();
                $table->unique(['learner_id', 'course_id', 'module_id'], 'learner_course_unit_unique');
            });
        }

        if (!$portal->hasTable('learner_course_unit_selection_histories')) {
            $portal->create('learner_course_unit_selection_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('learner_id')->index();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('module_id')->index();
                $table->string('action', 30);
                $table->json('old_value')->nullable();
                $table->json('new_value')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable()->index();
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // The CRM migration is the schema authority. This duplicate is intentionally non-destructive.
    }
};
