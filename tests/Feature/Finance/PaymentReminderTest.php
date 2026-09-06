<?php

use App\Enums\ChargeStatus;
use App\Jobs\SendPaymentReminderJob;
use App\Mail\PaymentReminderMail;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/** Crea un cobro pendiente para el miembro y devuelve la fila charge_member. */
function pendingChargeMemberFor(Member $member): ChargeMember
{
    $charge = Charge::factory()->create(['due_date' => now()->addDays(2)]);
    $charge->members()->attach($member->id, [
        'amount' => 30,
        'status' => ChargeStatus::Pending->value,
    ]);

    return ChargeMember::query()
        ->where('charge_id', $charge->id)
        ->where('member_id', $member->id)
        ->firstOrFail();
}

it('el recordatorio de pago se envía al email de contacto de la familia', function () {
    Mail::fake();

    $member = Member::factory()->create(['email' => 'nino@example.com']);
    $family = Family::factory()->create(['contact_email' => 'familia@example.com']);
    $family->members()->attach($member->id, ['relationship' => 'madre']);

    $chargeMember = pendingChargeMemberFor($member);

    (new SendPaymentReminderJob($chargeMember->id))->handle();

    Mail::assertSent(PaymentReminderMail::class, function (PaymentReminderMail $mail) {
        return $mail->hasTo('familia@example.com');
    });
    expect($chargeMember->fresh()->reminder_sent_at)->not->toBeNull();
});

it('sin familia con email, el recordatorio cae al email del propio miembro', function () {
    Mail::fake();

    $member = Member::factory()->create(['email' => 'miembro@example.com']);
    $chargeMember = pendingChargeMemberFor($member);

    (new SendPaymentReminderJob($chargeMember->id))->handle();

    Mail::assertSent(PaymentReminderMail::class, function (PaymentReminderMail $mail) {
        return $mail->hasTo('miembro@example.com');
    });
    expect($chargeMember->fresh()->reminder_sent_at)->not->toBeNull();
});

it('sin ningún email de contacto no se envía nada y NO se marca reminder_sent_at', function () {
    Mail::fake();

    $member = Member::factory()->create(['email' => null]);
    $chargeMember = pendingChargeMemberFor($member);

    (new SendPaymentReminderJob($chargeMember->id))->handle();

    Mail::assertNothingSent();
    expect($chargeMember->fresh()->reminder_sent_at)->toBeNull();
});

it('el comando de recordatorios avisa de los cobros sin email y solo encola los demás', function () {
    Queue::fake();

    $withEmail = Member::factory()->create(['email' => 'con-email@example.com']);
    $withoutEmail = Member::factory()->create(['email' => null, 'first_name' => 'Sin', 'last_name' => 'Correo']);

    $charge = Charge::factory()->create(['due_date' => now()->addDay()]);
    $charge->members()->attach([
        $withEmail->id => ['amount' => 30, 'status' => ChargeStatus::Pending->value],
        $withoutEmail->id => ['amount' => 30, 'status' => ChargeStatus::Pending->value],
    ]);

    $this->artisan('charges:send-reminders')
        ->expectsOutputToContain('Recordatorios encolados: 1.')
        ->expectsOutputToContain('Sin email de contacto')
        ->expectsOutputToContain('Sin Correo')
        ->assertSuccessful();

    Queue::assertPushed(SendPaymentReminderJob::class, 1);
});
