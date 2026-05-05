<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$token = Http::asForm()->post("https://login.microsoftonline.com/" . env('MS_TENANT_ID') . "/oauth2/v2.0/token", [
    'grant_type' => 'client_credentials',
    'client_id' => env('MS_CLIENT_ID'),
    'client_secret' => env('MS_CLIENT_SECRET'),
    'scope' => 'https://graph.microsoft.com/.default',
])->json()['access_token'];

$skus = Http::withToken($token)->get('https://graph.microsoft.com/v1.0/subscribedSkus')->json();

file_put_contents('skus.json', json_encode($skus, JSON_PRETTY_PRINT));
echo "Saved to skus.json";
