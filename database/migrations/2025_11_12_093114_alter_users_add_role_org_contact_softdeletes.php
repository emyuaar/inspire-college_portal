<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add columns only if they don't exist already
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'org_id')) {
                $table->unsignedBigInteger('org_id')->nullable()->after('role_id');
            }
            if (!Schema::hasColumn('users', 'contact')) {
                $table->string('contact', 20)->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add FK in a separate step to avoid issues when column exists but FK doesn't
        Schema::table('users', function (Blueprint $table) {
            // Only add FK if roles table exists and FK not already present
            if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
                // Make sure the column is unsigned and nullable before constraining
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop FK first if it exists, then the column(s)
            // Default FK name: users_role_id_foreign
            try {
                $table->dropForeign('users_role_id_foreign');
            } catch (\Throwable $e) {
                // ignore if it doesn't exist
            }

            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropColumn('role_id');
            }
            if (Schema::hasColumn('users', 'org_id')) {
                $table->dropColumn('org_id');
            }
            if (Schema::hasColumn('users', 'contact')) {
                $table->dropColumn('contact');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
