<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::connection('mysql_portal')->select('SHOW TABLES');
echo "Tables in database:\n";
foreach ($result as $row) {
    $table = (array)$row;
    $tableName = reset($table);
    if (strpos($tableName, 'status') !== false) {
        echo "  **$tableName**\n";
    } else {
        echo "  - $tableName\n";
    }
}
