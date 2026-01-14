<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mysql_portal')->table('users', function (Blueprint $table) {
            $table->string('ms_user_id', 80)->nullable()->after('password');
            $table->timestamp('ms_provisioned_at')->nullable()->after('ms_user_id');
            $table->boolean('ms_license_assigned')->default(false)->after('ms_provisioned_at');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->table('users', function (Blueprint $table) {
            $table->dropColumn(['ms_user_id', 'ms_provisioned_at', 'ms_license_assigned']);
        });
    }
};
