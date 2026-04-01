<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;

echo "===== GLOBAL LATEST ORDERS (CRM) =====\n";
$latestOrders = Order::latest()->limit(5)->get();
foreach($latestOrders as $o) {
    echo "Order #{$o->id} - Learner: {$o->learner_id} - Status: {$o->status_id} - Amount: {$o->amount} - Mode: {$o->payment_mode} - Created: {$o->created_at}\n";
}

echo "===== GLOBAL LATEST PARTNER INSTALLMENTS (PORTAL) =====\n";
$latestInst = PartnerLearnerInstallment::latest()->limit(5)->get();
foreach($latestInst as $i) {
    echo "Inst #{$i->id} - Learner: {$i->learner_id} - Partner: {$i->partner_id} - No: {$i->installment_no} - Status: {$i->status} - Paid: {$i->paid_amount} - Paid At: {$i->paid_at}\n";
}
