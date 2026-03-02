<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AssignmentSubmission;

try {
    $sub = AssignmentSubmission::latest()->first();
    echo "Latest submission ID: " . ($sub ? $sub->id : 'none') . "\n";
    if ($sub) {
        echo "Attributes:\n";
        print_r($sub->getAttributes());
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
