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
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            if (!Schema::connection('mysql_portal')->hasColumn('assignment_submissions', 'submitted_by_type')) {
                $table->string('submitted_by_type')->default('learner')->after('attempt_no');
                $table->unsignedBigInteger('submitted_by_id')->nullable()->after('submitted_by_type');
                $table->string('submitted_by_name')->nullable()->after('submitted_by_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn(['submitted_by_type', 'submitted_by_id', 'submitted_by_name']);
        });
    }
};
