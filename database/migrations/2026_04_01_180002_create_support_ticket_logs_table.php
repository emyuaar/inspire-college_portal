<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_crm';

    public function up(): void
    {
        Schema::connection('mysql_crm')->create('support_ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('action');              // claimed, reassigned, status_changed, closed
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->string('actor_type');          // crm_user, system
            $table->unsignedBigInteger('actor_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_crm')->dropIfExists('support_ticket_logs');
    }
};
