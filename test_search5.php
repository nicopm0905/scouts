<?php

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$creds = json_decode(file_get_contents(storage_path('app/google-credentials.json')), true);
$now = time();
$jwt = JWT::encode([
    'iss' => $creds['client_email'],
    'scope' => 'https://www.googleapis.com/auth/drive',
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $now,
    'exp' => $now + 3600,
], $creds['private_key'], 'RS256');

$tokenRes = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt,
]);
$token = $tokenRes->json('access_token');
$client = Http::withoutVerifying()->withToken($token);

$res = $client->get('https://www.googleapis.com/drive/v3/files/1NImG5FjWPR3R3w-tlR1kMiZLzBqvJ9-B', [
    'supportsAllDrives' => 'true',
    'fields' => 'id,name,createdTime',
]);

print_r($res->json());

$res2 = $client->get('https://www.googleapis.com/drive/v3/files', [
    'supportsAllDrives' => 'true',
    'includeItemsFromAllDrives' => 'true',
    'corpora' => 'drive',
    'driveId' => '0AGvk8nTS9d4OUk9PVA',
    'fields' => 'files(id,name,createdTime,trashed)',
    'orderBy' => 'createdTime desc',
]);
echo "\nLatest files in Shared Drive:\n";
foreach (array_slice($res2->json('files'), 0, 5) as $f) {
    echo "{$f['name']} ({$f['id']}) - created at {$f['createdTime']} [trashed: ".($f['trashed'] ? 'yes' : 'no')."]\n";
}
