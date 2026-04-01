<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;

$partner = User::where('id', 1)->first(); // Use ID 1 based on common test partner
if (!$partner) {
    echo "No partner found with ID 1\n";
    $partner = User::whereColumn('id', 'org_id')->first();
}

if ($partner) {
    echo "Testing counts for Partner: {$partner->first_name} (ID: {$partner->id})\n";
    $learnerIds = User::myLearners($partner->id)->pluck('id');
    echo "Learner IDs count: " . count($learnerIds) . " (" . implode(',', $learnerIds->toArray()) . ")\n";

    $instCount = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)->count();
    $paidInstCount = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)->where('status', 'paid')->count();
    echo "Total Installments: $instCount, Paid: $paidInstCount\n";

    $orderCount = Order::whereIn('learner_id', $learnerIds)->count();
    $paidOrderCount = Order::whereIn('learner_id', $learnerIds)->where('status_id', 1)->count();
    $fullOrderCount = Order::whereIn('learner_id', $learnerIds)->where('status_id', 1)->where('payment_mode', 'full')->count();
    $instOrderCount = Order::whereIn('learner_id', $learnerIds)->where('status_id', 1)->where('payment_mode', 'installment')->count();
    echo "Total Orders: $orderCount, Paid: $paidOrderCount, Paid Full: $fullOrderCount, Paid Installment: $instOrderCount\n";
} else {
    echo "No partner users found to test.\n";
}
