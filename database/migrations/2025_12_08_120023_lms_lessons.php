<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql_portal')->create('lms_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('module_id');   // lms_course_modules.id
            $table->string('title');
            $table->longText('content')->nullable();   // HTML
            $table->string('video_url')->nullable();
            $table->string('file_path')->nullable();   // pdf, doc etc
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('mysql_portal')->dropIfExists('lms_lessons');
    }
};