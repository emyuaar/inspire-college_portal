<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            if (Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'file_path')) {
                // just in case rollback needed
                $table->string('file_path')->nullable()->after('file_name');
            }
        });
    }
};
