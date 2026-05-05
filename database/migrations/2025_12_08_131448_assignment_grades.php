<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')->create('assignment_grades', function (Blueprint $table) {
            $table->id();

            // FK: assignment_submissions.id
            $table->foreignId('submission_id')
                  ->constrained('assignment_submissions')
                  ->cascadeOnDelete();

            $table->integer('marks')->nullable();
            $table->text('feedback')->nullable();

            // admin/teacher ID (no FK unless needed)
            $table->unsignedBigInteger('graded_by');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->dropIfExists('assignment_grades');
    }
};
