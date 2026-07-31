<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_crm';

    public function up(): void
    {
        Schema::connection('mysql_crm')->create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sender_type', ['partner', 'crm_user', 'system']);
            $table->unsignedBigInteger('sender_id');          // portal users.id or crm users.id depending on type
            $table->longText('body');
            $table->boolean('is_internal_note')->default(false); // true = never shown to partner
            $table->string('attachment_path')->nullable();       // future use
            $table->string('attachment_name')->nullable();       // future use
            $table->timestamps();

            $table->index(['ticket_id', 'is_internal_note']);
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_crm')->dropIfExists('support_messages');
    }
};
