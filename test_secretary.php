<?php

use App\Services\Drive\DriveStructureService;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$drive = app(DriveStructureService::class);
try {
    $folderId = $drive->getOrCreateSecretaryFolder();
    echo "Folder ID for SECRETARÍA 26/27 is: $folderId\n";
} catch (Throwable $e) {
    echo 'Exception: '.$e->getMessage()."\n";
}
