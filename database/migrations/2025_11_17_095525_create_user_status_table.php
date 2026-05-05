<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_status', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();   // Active, Inactive, Suspended, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_portal')->dropIfExists('user_status');
    }
};
