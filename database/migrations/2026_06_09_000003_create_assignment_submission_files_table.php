<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql_portal')->hasTable('assignment_submission_files')) {
            return;
        }

        Schema::connection('mysql_portal')->create('assignment_submission_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_submission_id')->index();
            $table->string('file_name');
            $table->string('stored_file_name')->nullable();
            $table->string('sharepoint_item_id')->nullable()->index();
            $table->string('sharepoint_path')->nullable();
            $table->text('sharepoint_url')->nullable();
            $table->string('drive_id')->nullable()->index();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->dropIfExists('assignment_submission_files');
    }
};
