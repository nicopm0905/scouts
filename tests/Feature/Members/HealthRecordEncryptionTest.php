<?php

use App\Models\HealthRecord;
use App\Models\Member;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

it('persiste los datos médicos cifrados en la base de datos', function () {
    $record = HealthRecord::factory()->create([
        'allergies' => 'Frutos secos',
        'intolerances' => 'Lactosa',
        'medication' => 'Ventolin si crisis',
        'observations' => 'Asma leve',
        'health_card_number' => 'AN1234567890',
    ]);

    $raw = DB::table('health_records')->where('id', $record->id)->first();

    // El valor crudo en BD no es el texto plano...
    expect($raw->allergies)->not->toBe('Frutos secos')
        ->and($raw->intolerances)->not->toBe('Lactosa')
        ->and($raw->medication)->not->toBe('Ventolin si crisis')
        ->and($raw->observations)->not->toBe('Asma leve')
        ->and($raw->health_card_number)->not->toBe('AN1234567890');

    // ...y es un payload de cifrado válido de Laravel.
    expect(Crypt::decryptString($raw->allergies))->toBe('Frutos secos')
        ->and(Crypt::decryptString($raw->health_card_number))->toBe('AN1234567890');

    // El accessor del modelo descifra de forma transparente.
    $fresh = HealthRecord::find($record->id);
    expect($fresh->allergies)->toBe('Frutos secos')
        ->and($fresh->intolerances)->toBe('Lactosa')
        ->and($fresh->medication)->toBe('Ventolin si crisis')
        ->and($fresh->observations)->toBe('Asma leve')
        ->and($fresh->health_card_number)->toBe('AN1234567890');
});

it('admite valores nulos en los campos cifrados', function () {
    $record = HealthRecord::factory()->create([
        'allergies' => null,
        'intolerances' => null,
        'medication' => null,
        'observations' => null,
        'health_card_number' => null,
    ]);

    expect($record->fresh()->allergies)->toBeNull()
        ->and($record->fresh()->health_card_number)->toBeNull();
});

it('registra los cambios de la ficha sanitaria en activity_log sin volcar el contenido médico', function () {
    $record = HealthRecord::factory()->create(['allergies' => 'Polen']);

    $record->update(['allergies' => 'Polen y ácaros']);

    $activity = Activity::query()
        ->where('log_name', 'health_record')
        ->where('subject_type', HealthRecord::class)
        ->where('subject_id', $record->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $properties = $activity->properties->toArray();

    // Queda constancia de QUÉ campo cambió, pero el valor está redactado.
    expect($properties['attributes'])->toHaveKey('allergies')
        ->and($properties['attributes']['allergies'])->toBe('[redactado]')
        ->and($properties['old']['allergies'])->toBe('[redactado]');

    // Y en ningún caso el JSON contiene el texto médico en claro.
    expect(json_encode($properties))->not->toContain('Polen');
});

it('el comando health:encrypt-existing cifra valores en claro y es idempotente', function () {
    $member = Member::factory()->create();

    // Simula un registro anterior al cifrado: valores en claro insertados a pelo.
    $id = DB::table('health_records')->insertGetId([
        'member_id' => $member->id,
        'allergies' => 'Gluten',
        'health_card_number' => 'AN0000000001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('health:encrypt-existing')->assertSuccessful();

    $raw = DB::table('health_records')->where('id', $id)->first();
    expect($raw->allergies)->not->toBe('Gluten')
        ->and(Crypt::decryptString($raw->allergies))->toBe('Gluten');

    // Segunda pasada: no debe re-cifrar (el modelo sigue descifrando bien).
    $this->artisan('health:encrypt-existing')->assertSuccessful();

    $fresh = HealthRecord::find($id);
    expect($fresh->allergies)->toBe('Gluten')
        ->and($fresh->health_card_number)->toBe('AN0000000001');
});
