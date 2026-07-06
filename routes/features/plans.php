<?php

use App\Http\Controllers\Plans\ActivityController;
use App\Http\Controllers\Plans\ActivityObjectiveController;
use App\Http\Controllers\Plans\ActivityScheduleController;
use App\Http\Controllers\Plans\BranchPlanController;
use App\Http\Controllers\Plans\BranchPlanObjectiveController;
use Illuminate\Support\Facades\Route;

/*
| Feature: Plan de rama y biblioteca de actividades (Agente E).
| Registrado automáticamente por routes/web.php dentro del grupo 'auth'.
*/
Route::middleware('auth')->group(function () {
    Route::resource('branch-plans', BranchPlanController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::post('branch-plans/{branch_plan}/objectives', [BranchPlanObjectiveController::class, 'store'])
        ->name('branch-plans.objectives.store');
    Route::put('branch-plan-objectives/{objective}', [BranchPlanObjectiveController::class, 'update'])
        ->name('branch-plans.objectives.update');
    Route::delete('branch-plan-objectives/{objective}', [BranchPlanObjectiveController::class, 'destroy'])
        ->name('branch-plans.objectives.destroy');

    Route::resource('activities', ActivityController::class);
    Route::post('activities/{activity}/duplicate', [ActivityController::class, 'duplicate'])
        ->name('activities.duplicate');

    Route::post('activities/{activity}/events/{event}', [ActivityScheduleController::class, 'store'])
        ->name('activities.events.attach');
    Route::delete('activities/{activity}/events/{event}', [ActivityScheduleController::class, 'destroy'])
        ->name('activities.events.detach');

    Route::post('activities/{activity}/objectives/{objective}', [ActivityObjectiveController::class, 'store'])
        ->name('activities.objectives.attach');
    Route::delete('activities/{activity}/objectives/{objective}', [ActivityObjectiveController::class, 'destroy'])
        ->name('activities.objectives.detach');
});
