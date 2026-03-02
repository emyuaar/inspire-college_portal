<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop from Portal DB (Mistake cleanup)
        Schema::connection('mysql_portal')->dropIfExists('partner_learner_installments');

        // Create in CRM DB (Correct location)
        if (!Schema::connection('mysql_crm')->hasTable('partner_learner_installments')) {
            Schema::connection('mysql_crm')->create('partner_learner_installments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->unsignedBigInteger('learner_id');
                $table->unsignedBigInteger('enrolment_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('course_id');

                $table->string('plan_type')->default('installments_only');
                $table->decimal('total_amount', 10, 2);
                $table->decimal('deposit_amount', 10, 2)->nullable();

                $table->decimal('installment_amount', 10, 2);
                $table->integer('installments_count');
                $table->integer('installment_no');

                $table->date('due_date');

                $table->string('status')->default('pending');

                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->timestamp('paid_at')->nullable();

                $table->string('payment_reference')->nullable();
                $table->string('stripe_payment_intent_id')->nullable();
                $table->string('receipt_path')->nullable();

                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index('partner_id');
                $table->index('learner_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_crm')->dropIfExists('partner_learner_installments');
        // Ideally recreate in portal if reversing, or just drop. 
        // For safety/reversibility we'll leave it dropped or recreate empty.
    }
};
