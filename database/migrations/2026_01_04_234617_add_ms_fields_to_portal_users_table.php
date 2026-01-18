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
        Schema::connection('mysql_portal')->table('users', function (Blueprint $table) {

            // Microsoft Graph user object id
            if (!Schema::connection('mysql_portal')->hasColumn('users', 'ms_user_id')) {
                $table->string('ms_user_id', 80)
                      ->nullable()
                      ->after('email_address');
                $table->index('ms_user_id'); // Add index only if column added
            }

            // When MS account was provisioned
            if (!Schema::connection('mysql_portal')->hasColumn('users', 'ms_provisioned_at')) {
                $table->timestamp('ms_provisioned_at')
                      ->nullable()
                      ->after('ms_user_id');
            }

            // Whether student/web license assigned
            if (!Schema::connection('mysql_portal')->hasColumn('users', 'ms_license_assigned')) {
                $table->boolean('ms_license_assigned')
                      ->default(false)
                      ->after('ms_provisioned_at');
            }

            // Error message (Added in fix)
            if (!Schema::connection('mysql_portal')->hasColumn('users', 'ms_error_message')) {
                $table->text('ms_error_message')
                      ->nullable()
                      ->after('ms_license_assigned');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_portal')->table('users', function (Blueprint $table) {

            $table->dropIndex(['ms_user_id']);

            $table->dropColumn([
                'ms_user_id',
                'ms_provisioned_at',
                'ms_license_assigned',
            ]);
        });
    }
};
