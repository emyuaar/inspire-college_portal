<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = DB::connection('mysql_portal')->select('DESCRIBE assignment_submissions');
foreach ($columns as $col) {
    echo $col->Field . ' | ' . $col->Type . ' | ' . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . " | Default: " . ($col->Default ?? 'none') . "\n";
}
