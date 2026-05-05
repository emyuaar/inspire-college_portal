<?php
use App\Models\Crm\EnrolmentStatus;
$statuses = EnrolmentStatus::all();
foreach($statuses as $s) {
    echo "ID: {$s->id} - Status: {$s->status}\n";
}
