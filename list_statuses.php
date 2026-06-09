<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Assignment Submission Statuses Table Structure:\n";
$structure = DB::connection('mysql_portal')->select('DESCRIBE assignment_submission_statuses');
foreach ($structure as $col) {
    echo "  {$col->Field} ({$col->Type})\n";
}

echo "\nAssignment Submission Statuses:\n";
$statuses = DB::connection('mysql_portal')->select('SELECT * FROM assignment_submission_statuses ORDER BY id');
foreach ($statuses as $status) {
    $row = (array)$status;
    echo "  " . implode(' | ', $row) . "\n";
}
