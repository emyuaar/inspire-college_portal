<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Crm\EnrolmentStatus;

$st = EnrolmentStatus::where('status', 'like', '%plan%')->get();
foreach($st as $s) {
    echo "ID: {$s->id} - Status: {$s->status}\n";
}

$all = EnrolmentStatus::all();
foreach($all as $a) {
    echo "ID: {$a->id} - Status: {$a->status}\n";
}
