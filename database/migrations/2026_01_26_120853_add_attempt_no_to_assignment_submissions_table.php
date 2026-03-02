<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'attempt_no')) {
            Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
                $table->integer('attempt_no')->default(1)->after('status_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn('attempt_no');
        });
    }
};
