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
        Schema::connection('mysql_crm')->table('partner_learner_installments', function (Blueprint $table) {
            if (! Schema::connection('mysql_crm')->hasColumn('partner_learner_installments', 'submitted_amount')) {
                $table->decimal('submitted_amount', 10, 2)->nullable()->after('paid_amount');
            }
            if (! Schema::connection('mysql_crm')->hasColumn('partner_learner_installments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_amount');
            }
            if (! Schema::connection('mysql_crm')->hasColumn('partner_learner_installments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::connection('mysql_crm')->hasColumn('partner_learner_installments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_crm')->table('partner_learner_installments', function (Blueprint $table) {
            $table->dropColumn(['submitted_amount', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
