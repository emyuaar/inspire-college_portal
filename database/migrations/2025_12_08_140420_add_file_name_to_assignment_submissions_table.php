<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')
            ->table('assignment_submissions', function (Blueprint $table) {
                $table->string('file_name')
                      ->after('learner_id')
                      ->nullable(); // safe, older rows ke liye
            });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')
            ->table('assignment_submissions', function (Blueprint $table) {
                $table->dropColumn('file_name');
            });
    }
};
