<?php

use App\Http\Controllers\Members\AttendanceController;
use App\Http\Controllers\Members\ChangeRequestController;
use App\Http\Controllers\Members\ConsentController;
use App\Http\Controllers\Members\FamilyAccountController;
use App\Http\Controllers\Members\FamilyController;
use App\Http\Controllers\Members\HealthRecordController;
use App\Http\Controllers\Members\LeaderProfileController;
use App\Http\Controllers\Members\LeaderTrainingController;
use App\Http\Controllers\Members\MemberController;
use App\Http\Controllers\Members\MemberImportController;
use Illuminate\Support\Facades\Route;

/*
| Feature: Miembros y familias (Agente A).
| Registrado automáticamente por routes/web.php dentro del grupo 'auth'.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Importador CSV (antes del resource para que "members/import" no choque con {member}).
    Route::get('members/import', [MemberImportController::class, 'create'])->name('members.import');
    Route::post('members/import', [MemberImportController::class, 'store'])->name('members.import.store');
    Route::get('members/import/template', [MemberImportController::class, 'template'])->name('members.import.template');

    // Exportación CSV del censo (antes del resource para que "members/export" no choque con {member}).
    Route::get('members/export', [MemberController::class, 'export'])->name('members.export');

    Route::resource('members', MemberController::class);

    // Ficha sanitaria, consentimientos y perfil de responsable.
    Route::put('members/{member}/health-record', [HealthRecordController::class, 'update'])->name('members.health-record.update');
    Route::put('members/{member}/consents', [ConsentController::class, 'update'])->name('members.consents.update');
    Route::put('members/{member}/leader-profile', [LeaderProfileController::class, 'update'])->name('members.leader-profile.update');
    Route::post('members/{member}/leader-trainings', [LeaderTrainingController::class, 'store'])->name('members.leader-trainings.store');
    Route::delete('leader-trainings/{leaderTraining}', [LeaderTrainingController::class, 'destroy'])->name('leader-trainings.destroy');

    // Familias: gestión de grupos familiares y su vínculo con miembros.
    Route::resource('families', FamilyController::class)->except(['show']);
    Route::post('members/{member}/families', [FamilyController::class, 'attach'])->name('members.families.attach');
    Route::delete('members/{member}/families/{family}', [FamilyController::class, 'detach'])->name('members.families.detach');

    // Cuentas de acceso de las familias al portal.
    Route::post('families/{family}/invite', [FamilyAccountController::class, 'invite'])->name('families.invite');
    Route::delete('families/{family}/accounts/{user}', [FamilyAccountController::class, 'revoke'])->name('families.accounts.revoke');

    // Control de asistencia.
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // Revisiones de datos que envían las familias desde el portal.
    Route::get('revisiones-familias', [ChangeRequestController::class, 'index'])->name('member-change-requests.index');
    Route::post('revisiones-familias/{changeRequest}/aprobar', [ChangeRequestController::class, 'approve'])->name('member-change-requests.approve');
    Route::post('revisiones-familias/{changeRequest}/rechazar', [ChangeRequestController::class, 'reject'])->name('member-change-requests.reject');
});
