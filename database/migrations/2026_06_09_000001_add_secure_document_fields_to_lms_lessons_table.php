<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('mysql_portal')->hasTable('lms_lessons')) {
            return;
        }

        Schema::connection('mysql_portal')->table('lms_lessons', function (Blueprint $table) {
            if (!Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'resource_type')) {
                $table->string('resource_type')->nullable()->after('file_path');
            }
            if (!Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'document_title')) {
                $table->string('document_title')->nullable()->after('resource_type');
            }
            if (!Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'viewer_mode')) {
                $table->string('viewer_mode')->nullable()->after('document_title');
            }
            if (!Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'allow_print')) {
                $table->boolean('allow_print')->default(false)->after('viewer_mode');
            }
            if (!Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'allow_download')) {
                $table->boolean('allow_download')->default(false)->after('allow_print');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::connection('mysql_portal')->hasTable('lms_lessons')) {
            return;
        }

        Schema::connection('mysql_portal')->table('lms_lessons', function (Blueprint $table) {
            $columns = array_filter([
                Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'allow_download') ? 'allow_download' : null,
                Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'allow_print') ? 'allow_print' : null,
                Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'viewer_mode') ? 'viewer_mode' : null,
                Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'document_title') ? 'document_title' : null,
                Schema::connection('mysql_portal')->hasColumn('lms_lessons', 'resource_type') ? 'resource_type' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
