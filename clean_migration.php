<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migrationName = '2026_01_26_120853_add_attempt_no_to_assignment_submissions_table';

$deleted = DB::connection('mysql_portal')->table('migrations')->where('migration', $migrationName)->delete();

if ($deleted) {
    echo "Successfully deleted migration record: $migrationName\n";
} else {
    echo "Migration record not found: $migrationName\n";
}
