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
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {

            // 1. Ensure status column exists - if missing, create as string
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'status')) {
                $table->string('status')->default('submitted')->after('stored_file_name');
            }

            // 2. Ensure drive_id exists
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'drive_id')) {
                $table->string('drive_id')->nullable()->after('sharepoint_url');
            }

            // 3. Ensure file_size exists
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_size')) {
                $table->bigInteger('file_size')->nullable()->after('drive_id');
            }

            // 4. Ensure mime_type exists
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_size');
            }

            // 5. Ensure attempt_no exists
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'attempt_no')) {
                $table->integer('attempt_no')->default(1)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'drive_id')) {
                $table->dropColumn('drive_id');
            }
        });
    }
};
