<?php

use App\Services\Drive\DriveServiceInterface;
use App\Services\Drive\DriveStructureService;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$drive = app(DriveServiceInterface::class);
echo "Result of searchFolder:\n";
try {
    $folderId = $drive->searchFolder('SECRETARÍA 26/27');
    echo 'Folder ID found: '.($folderId ?: 'None')."\n";
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

echo "\nResult of getOrCreateSecretaryFolder:\n";
try {
    $structure = app(DriveStructureService::class);
    $id = $structure->getOrCreateSecretaryFolder();
    echo 'Folder ID returned: '.$id."\n";
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
