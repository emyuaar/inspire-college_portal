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
        Schema::connection('mysql_portal')->create('partner_learner_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('learner_id');
            $table->unsignedBigInteger('enrolment_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('course_id');

            $table->string('plan_type')->default('installments_only'); // 'deposit_installments' or 'installments_only'
            $table->decimal('total_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->nullable();

            $table->decimal('installment_amount', 10, 2);
            $table->integer('installments_count');
            $table->integer('installment_no'); // 0 for deposit if needed, else 1..N

            $table->date('due_date');

            $table->string('status')->default('pending'); // pending, partial, paid, overdue

            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();

            $table->string('payment_reference')->nullable(); // manual ref
            $table->string('stripe_payment_intent_id')->nullable(); // for 1-time charges if any
            $table->string('receipt_path')->nullable(); // proof

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('partner_id');
            $table->index('learner_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_learner_installments');
    }
};
