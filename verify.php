<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(2240);
if (!$user) {
    echo "User 2240 not found\n";
    exit;
}

echo "Testing user 2240 enrolments...\n";

foreach ($user->enrolments as $enr) {
    if ($enr->status_id == 1 || $enr->status_id == 2) {
        $status = $enr->installment_access_status;
        echo "\nEnrolment ID: {$enr->id}\n";
        echo "Allowed: " . ($status->allowed ? 'Yes' : 'No') . "\n";
        echo "Grace Active: " . (($status->grace_active ?? false) ? 'Yes' : 'No') . "\n";
        echo "Grace Until: " . ($status->grace_until ?? 'N/A') . "\n";
        echo "Reason: " . ($status->reason ?? 'None') . "\n";
        if (isset($status->due_info)) {
            echo "Due Info: Date=" . $status->due_info['date'] . ", Amount=" . $status->due_info['amount'] . ", Label=" . $status->due_info['label'] . "\n";
        }
    }
}
