<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_portal');

        if (!$schema->hasColumn('assignment_submissions', 'stored_file_name')) {
            $schema->table('assignment_submissions', function (Blueprint $table) {
                $table->string('stored_file_name')->nullable()->after('file_name');
            });
        }

        if (!$schema->hasColumn('assignment_submission_files', 'stored_file_name')) {
            $schema->table('assignment_submission_files', function (Blueprint $table) {
                $table->string('stored_file_name')->nullable()->after('file_name');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql_portal');

        if ($schema->hasColumn('assignment_submission_files', 'stored_file_name')) {
            $schema->table('assignment_submission_files', function (Blueprint $table) {
                $table->dropColumn('stored_file_name');
            });
        }

        if ($schema->hasColumn('assignment_submissions', 'stored_file_name')) {
            $schema->table('assignment_submissions', function (Blueprint $table) {
                $table->dropColumn('stored_file_name');
            });
        }
    }
};
