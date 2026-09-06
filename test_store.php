<?php

use App\Http\Controllers\Events\EventController;
use App\Http\Requests\Events\StoreEventRequest;
use App\Models\User;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Drive\DriveStructureService;
use Illuminate\Support\Facades\Auth;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $user = User::first();
    Auth::login($user);

    $c = app(EventController::class);
    $r = new StoreEventRequest;
    $r->merge([
        'title' => 'Test Event 3',
        'type' => 'campamento',
        'start_at' => now()->addDays(2)->toDateTimeString(),
        'end_at' => now()->addDays(3)->toDateTimeString(),
        'branches' => ['castor'],
        'coordinator' => $user->id,
    ]);
    $r->setUserResolver(fn () => $user);
    $r->setValidator(
        validator($r->all(), [
            'title' => 'required|string',
            'type' => 'required',
            'start_at' => 'required',
            'end_at' => 'required',
            'branches' => 'array',
        ])
    );

    $c->store($r, app(DriveStructureService::class), app(DriveServiceInterface::class));
} catch (Throwable $e) {
    echo 'Exception: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n";
    echo $e->getTraceAsString();
}
