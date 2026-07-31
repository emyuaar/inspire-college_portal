<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_crm';

    public function up(): void
    {
        Schema::connection('mysql_crm')->create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // e.g. TKT-00001
            $table->unsignedBigInteger('partner_id');        // portal users.id
            $table->string('subject');
            $table->string('category')->nullable();          // general, billing, technical, etc.
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'open',
                'assigned',
                'waiting_support',
                'waiting_partner',
                'resolved',
                'closed',
            ])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable(); // CRM users.id
            $table->timestamp('last_message_at')->nullable();
            $table->enum('last_message_by', ['partner', 'crm_user', 'system'])->nullable();
            $table->boolean('unread_for_crm')->default(true);    // new from partner
            $table->boolean('unread_for_partner')->default(false); // new from CRM
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_crm')->dropIfExists('support_tickets');
    }
};
