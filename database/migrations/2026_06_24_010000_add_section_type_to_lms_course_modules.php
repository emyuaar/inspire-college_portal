<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_portal');

        $addedSectionType = !$schema->hasColumn('lms_course_modules', 'section_type');
        if ($addedSectionType) {
            $schema->table('lms_course_modules', function (Blueprint $table) {
                $table->string('section_type', 40)->default('unit')->index();
            });
        }

        DB::connection('mysql_portal')->statement(
            'ALTER TABLE `lms_course_modules` MODIFY `unit_type` VARCHAR(20) NULL DEFAULT NULL'
        );

        if (!$addedSectionType) {
            return;
        }

        DB::connection('mysql_portal')->table('lms_course_modules')->update(['section_type' => 'unit']);

        DB::connection('mysql_portal')->table('lms_course_modules')
            ->whereRaw('LOWER(TRIM(title)) LIKE ?', ['%guideline%'])
            ->update([
                'section_type' => 'guidelines',
                'included_in_completion' => false,
                'unit_type' => null,
                'credits' => null,
                'optional_group_id' => null,
            ]);

        DB::connection('mysql_portal')->table('lms_course_modules')
            ->where('section_type', 'unit')
            ->update([
                'included_in_completion' => true,
                'unit_type' => DB::raw("COALESCE(unit_type, 'mandatory')"),
            ]);
    }

    public function down(): void
    {
        // CRM migration is the destructive schema authority.
    }
};
