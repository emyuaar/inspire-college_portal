<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Default Connection: " . Config::get('database.default') . "\n";
echo "mysql_portal DB: " . Config::get('database.connections.mysql_portal.database') . "\n";
echo "mysql DB: " . Config::get('database.connections.mysql.database') . "\n";

try {
    $columns = DB::connection('mysql_portal')->getSchemaBuilder()->getColumnListing('assignment_submissions');
    echo "Columns in mysql_portal.assignment_submissions: " . implode(', ', $columns) . "\n";
} catch (\Exception $e) {
    echo "Error connecting to mysql_portal: " . $e->getMessage() . "\n";
}
