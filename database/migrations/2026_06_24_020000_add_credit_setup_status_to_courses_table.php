<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_website');
        if (!$schema->hasTable('courses')) return;

        if (!$schema->hasColumn('courses', 'credit_setup_status')) {
            $schema->table('courses', function (Blueprint $table) {
                $table->string('credit_setup_status', 30)->default('not_required');
            });
        }

        DB::connection('mysql_website')->table('courses')->where('completion_mode', 'standard')
            ->update(['credit_setup_status' => 'not_required']);
        DB::connection('mysql_website')->table('courses')->where('completion_mode', 'credit_based')
            ->where(function ($query) {
                $query->whereNull('credit_setup_status')->orWhere('credit_setup_status', 'not_required');
            })
            ->update(['credit_setup_status' => 'pending_setup']);
    }

    public function down(): void
    {
        // Website application owns destructive rollback for this column.
    }
};
