<?php

use App\Enums\ConsentType;
use App\Enums\SignatureStatus;
use App\Mail\SignatureRequestMail;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Family;
use App\Models\Member;
use App\Models\Signature;
use Illuminate\Support\Facades\Mail;

it('secretaría envía a firma un consentimiento y la familia recibe el email con el enlace', function () {
    Mail::fake();

    $user = userWithRole('secretaria');
    $member = Member::factory()->create();
    $family = Family::factory()->create(['contact_email' => 'familia@example.com']);
    $family->members()->attach($member->id, ['relationship' => 'madre']);

    $this->actingAs($user)
        ->post(route('signatures.consent.send', [$member->id, ConsentType::Rgpd->value]))
        ->assertRedirect();

    $signature = Signature::where('member_id', $member->id)->firstOrFail();
    expect($signature->status)->toBe(SignatureStatus::Sent);
    expect($signature->public_token)->not->toBeEmpty();

    Mail::assertSent(SignatureRequestMail::class, fn (SignatureRequestMail $mail) => $mail->hasTo('familia@example.com'));
});

it('la familia firma el consentimiento desde el enlace público y el Consent se sincroniza', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->create();
    $consent = Consent::factory()->for($member)->ofType(ConsentType::Imagen)->create(['granted' => false, 'signed_at' => null]);

    $this->actingAs($user)
        ->post(route('signatures.consent.send', [$member->id, ConsentType::Imagen->value]));

    $signature = Signature::where('member_id', $member->id)->firstOrFail();

    $this->get(route('public.signature.show', $signature->public_token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Signature')->where('status', 'sent'));

    $this->post(route('public.signature.sign', $signature->public_token), [
        'signer_name' => 'María Pérez',
        'signature_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        'accepted' => true,
    ])->assertRedirect();

    $signature->refresh();
    expect($signature->status)->toBe(SignatureStatus::Signed);
    expect($signature->signer_name)->toBe('María Pérez');
    expect($signature->signed_drive_file_id)->not->toBeNull();

    $consent->refresh();
    expect($consent->granted)->toBeTrue();
    expect($consent->signed_at)->not->toBeNull();
    expect($consent->drive_file_id)->toBe($signature->signed_drive_file_id);
});

it('no se puede firmar dos veces la misma solicitud', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->create();
    Consent::factory()->for($member)->ofType(ConsentType::Rgpd)->create();

    $this->actingAs($user)->post(route('signatures.consent.send', [$member->id, ConsentType::Rgpd->value]));
    $signature = Signature::where('member_id', $member->id)->firstOrFail();

    $payload = [
        'signer_name' => 'Padre Uno',
        'signature_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        'accepted' => true,
    ];

    $this->post(route('public.signature.sign', $signature->public_token), $payload)->assertRedirect();
    $this->post(route('public.signature.sign', $signature->public_token), $payload)->assertStatus(409);
});

it('un token inexistente devuelve 404', function () {
    $this->get(route('public.signature.show', 'token-que-no-existe'))->assertNotFound();
});

it('secretaría envía a firmar un documento institucional a varios miembros', function () {
    Mail::fake();

    $user = userWithRole('secretaria');
    $members = Member::factory()->count(2)->create();
    $document = Document::factory()->create(['drive_file_id' => null, 'external_url' => 'https://example.com/normativa.pdf']);

    $this->actingAs($user)
        ->post(route('signatures.document.send', $document->id), [
            'member_ids' => $members->pluck('id')->all(),
        ])->assertRedirect();

    expect(Signature::where('signable_type', $document->getMorphClass())->where('signable_id', $document->id)->count())->toBe(2);
    Mail::assertSent(SignatureRequestMail::class, 2);
});
