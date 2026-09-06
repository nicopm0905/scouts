<?php

use App\Mail\ExpiryAlertMail;
use App\Models\Document;
use App\Models\LeaderProfile;
use App\Models\LeaderTraining;
use Illuminate\Support\Facades\Mail;

it('el comando de caducidades envía UN email resumen a admin y secretaría', function () {
    Mail::fake();

    $admin = userWithRole('admin');
    $secretary = userWithRole('secretaria');
    $responsable = userWithRole('responsable', ['lobato']); // no debe recibirlo

    Document::factory()->expiringSoon()->create(['title' => 'Seguro RC']);

    $profile = LeaderProfile::factory()->create([
        'sexual_offenses_certificate_expires_at' => now()->addYears(2), // fuera de ventana
    ]);
    LeaderTraining::factory()->create([
        'leader_profile_id' => $profile->id,
        'expires_at' => now()->addDays(5),
    ]);

    $this->artisan('alerts:expiry')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertSent(ExpiryAlertMail::class, function (ExpiryAlertMail $mail) use ($admin, $secretary, $responsable) {
        return $mail->hasTo($admin->email)
            && $mail->hasTo($secretary->email)
            && ! $mail->hasTo($responsable->email)
            && $mail->documents->count() === 1
            && $mail->trainings->count() === 1;
    });
});

it('el comando de caducidades no envía email si no hay nada que atender', function () {
    Mail::fake();

    userWithRole('admin');

    $this->artisan('alerts:expiry')->assertSuccessful();

    Mail::assertNothingSent();
});
