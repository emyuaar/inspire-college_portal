<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {

            // missing columns (if not exist)
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_name')) {
                $table->string('file_name')->nullable()->after('learner_id');
            }

            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_item_id')) {
                $table->string('sharepoint_item_id')->nullable()->after('file_path');
            }

            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_path')) {
                $table->string('sharepoint_path')->nullable()->after('sharepoint_item_id');
            }

            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_url')) {
                $table->text('sharepoint_url')->nullable()->after('sharepoint_path');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_url')) {
                $table->dropColumn('sharepoint_url');
            }
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_path')) {
                $table->dropColumn('sharepoint_path');
            }
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'sharepoint_item_id')) {
                $table->dropColumn('sharepoint_item_id');
            }
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
