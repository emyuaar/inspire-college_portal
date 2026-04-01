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
        Schema::create('partner_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->index();
            $table->string('type');           // e.g. installment_overdue, payment_received, plan_required
            $table->string('title');
            $table->string('body');
            $table->string('icon')->default('bell');      // icon key: bell, warning, check, info
            $table->string('color')->default('slate');    // slate, red, green, amber, blue
            $table->string('action_url')->nullable();     // deep link
            $table->string('action_label')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();   // learner_id / installment_id etc.
            $table->string('related_type')->nullable();              // learner, installment, enrolment etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['partner_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_notifications');
    }
};
