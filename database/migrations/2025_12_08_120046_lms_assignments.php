<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql_portal')->create('lms_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->unsignedInteger('max_marks')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('mysql_portal')->dropIfExists('lms_assignments');
    }
};