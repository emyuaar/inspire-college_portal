<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql_portal')->create('lms_course_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id'); // direct_skills.courses.id
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('mysql_portal')->dropIfExists('lms_course_modules');
    }
};