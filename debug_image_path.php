<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Website\Course;

$courses = Course::whereNotNull('image')->take(5)->get();
foreach($courses as $c) {
    echo "IMAGE: " . $c->image . "\n";
}
