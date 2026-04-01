<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;

$partner = User::where('id', 2223)->first();

if ($partner) {
    echo "Inspecting Partner: {$partner->first_name} (ID: {$partner->id})\n";
    $learnerIds = User::myLearners($partner->id)->pluck('id');
    
    echo "===== INSTALLMENTS FOR LEARNER 2232 =====\n";
    $inst = PartnerLearnerInstallment::where('learner_id', 2232)->get();
    foreach($inst as $i) {
        echo "ID: {$i->id} - No: {$i->installment_no} - Status: [{$i->status}] - Due: {$i->due_date} - Paid Amount: {$i->paid_amount}\n";
    }

    echo "===== PENDING ORDERS FOR PARTNER LEARNERS =====\n";
    $orders = Order::whereIn('learner_id', $learnerIds)->where('status_id', '!=', 1)->get();
    foreach($orders as $o) {
        echo "Order #{$o->id} - Learner: {$o->learner_id} - Status: {$o->status_id} - Mode: {$o->payment_mode} - Amount: {$o->amount}\n";
    }
}
