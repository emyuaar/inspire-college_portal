<?php

use App\Models\Crm\Order;
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use Illuminate\Support\Facades\DB;

// Simulate finding the latest pending order
$order = Order::whereNull('status_id')->orderBy('id', 'desc')->first();

if (!$order) {
    echo "No pending order found.\n";
    exit;
}

echo "Found Pending Order ID: {$order->id}\n";
echo "Enrolment ID: {$order->enrolment_id}\n";

$enrolment = Enrolment::find($order->enrolment_id);

if (!$enrolment) {
    echo "Error: Enrolment not found.\n";
    exit;
}

echo "Current Enrolment Status ID: {$enrolment->status_id}\n";
if ($enrolment->status) {
    echo "Current Enrolment Status Name: {$enrolment->status->status}\n";
}

try {
    DB::connection('mysql_crm')->beginTransaction();
    DB::connection('mysql_portal')->beginTransaction();

    echo "Attempting updates...\n";

    // 1. Update Order
    $order->update([
        'status_id' => 1, // Paid
        'stripe_payment_id' => 'simulated_payment_intent',
    ]);
    echo "Order updated to Paid.\n";

    // 2. Update Enrolment
    $activeStatus = EnrolmentStatus::firstOrCreate(['status' => 'active']);
    echo "Active Status ID: {$activeStatus->id}\n";

    $enrolment->update(['status_id' => $activeStatus->id]);
    echo "Enrolment updated to Active.\n";

    // Commit?
    // DB::connection('mysql_crm')->commit();
    // DB::connection('mysql_portal')->commit();
    DB::connection('mysql_crm')->rollBack(); // Rollback so we don't mess up data
    DB::connection('mysql_portal')->rollBack();
    echo "Simulation Successful (Rolled Back).\n";

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    DB::connection('mysql_crm')->rollBack();
    DB::connection('mysql_portal')->rollBack();
}
