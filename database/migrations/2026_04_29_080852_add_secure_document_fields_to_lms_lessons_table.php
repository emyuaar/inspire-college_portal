<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_portal')->table('lms_lessons', function (Blueprint $table) {
            $table->string('resource_type')->nullable()->after('file_path');
            $table->string('document_title')->nullable()->after('resource_type');
            $table->string('viewer_mode')->nullable()->after('document_title');
            $table->boolean('allow_print')->default(false)->after('viewer_mode');
            $table->boolean('allow_download')->default(false)->after('allow_print');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_portal')->table('lms_lessons', function (Blueprint $table) {
            $table->dropColumn(['resource_type', 'document_title', 'viewer_mode', 'allow_print', 'allow_download']);
        });
    }
};
