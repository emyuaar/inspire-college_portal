<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;
use Illuminate\Support\Facades\DB;

$partner = User::where('id', 2223)->first();

if ($partner) {
    echo "Inspecting Partner: {$partner->first_name} (ID: {$partner->id})\n";
    $learnerIds = User::myLearners($partner->id)->pluck('id');
    
    echo "===== ORDERS =====\n";
    $orders = Order::whereIn('learner_id', $learnerIds)->get();
    foreach($orders as $o) {
        echo "Order #{$o->id} - Learner: {$o->learner_id} - Amount: {$o->amount} - Status: {$o->status_id} - Mode: {$o->payment_mode} - Created: {$o->created_at}\n";
    }

    echo "===== INSTALLMENTS =====\n";
    $inst = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)->get();
    foreach($inst as $i) {
        echo "Installment #{$i->id} - Learner: {$i->learner_id} - No: {$i->installment_no} - Status: {$i->status} - Paid: {$i->paid_amount} - Paid At: {$i->paid_at}\n";
    }

    echo "===== RAW CRM PAYMENTS =====\n";
    $payments = DB::connection('mysql_crm')->table('payments')->latest()->limit(5)->get();
    foreach($payments as $p) {
        echo "Payment ID: {$p->id} - Amount: {$p->amount_pence} - Status: {$p->status} - Meta: {$p->meta} - Created: {$p->created_at}\n";
    }
}
