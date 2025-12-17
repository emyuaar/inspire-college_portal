<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_portal')->create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            // Correct FK: references lms_assignments
            $table->foreignId('assignment_id')
                  ->constrained('lms_assignments')
                  ->cascadeOnDelete();

            $table->unsignedBigInteger('learner_id');
            $table->string('file_path');
            $table->enum('status', ['submitted', 'graded', 'resubmitted'])->default('submitted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->dropIfExists('assignment_submissions');
    }
};
