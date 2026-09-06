<?php

use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\UploadedFile;

it('permite descargar la plantilla Excel', function () {
    $user = userWithRole('secretaria');

    $response = $this->actingAs($user)->get(route('members.import.template'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('plantilla_miembros.xlsx');
});

it('importa miembros válidos desde un CSV', function () {
    $user = userWithRole('secretaria');

    $csv = "first_name,last_name,phone,email,role,birth_date,joined_at,active,notes\n"
        ."Ana,García Pérez,600111222,ana@example.com,lobato,2016-04-01,2023-09-01,1,\n"
        ."Luis,Martín Ruiz,600333444,luis@example.com,pionero,2011-01-15,2022-09-01,1,\n";

    $file = UploadedFile::fake()->createWithContent('miembros.csv', $csv);

    $response = $this->actingAs($user)->post(route('members.import.store'), ['file' => $file]);

    $response->assertRedirect(route('members.index'));
    $this->assertDatabaseHas('members', ['first_name' => 'Ana', 'last_name' => 'García Pérez']);
    $this->assertDatabaseHas('members', ['first_name' => 'Luis', 'last_name' => 'Martín Ruiz']);
});

it('reporta errores de fila sin abortar la importación de las filas válidas', function () {
    $user = userWithRole('secretaria');

    $csv = "first_name,last_name,phone,email,role,birth_date,joined_at,active,notes\n"
        ."Ana,García Pérez,600111222,ana@example.com,lobato,2016-04-01,2023-09-01,1,\n"
        .",Sin Nombre,600333444,mal@example.com,rama_invalida,2011-01-15,2022-09-01,1,\n";

    $file = UploadedFile::fake()->createWithContent('miembros.csv', $csv);

    $response = $this->actingAs($user)->post(route('members.import.store'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('members', ['first_name' => 'Ana']);
    $this->assertDatabaseMissing('members', ['last_name' => 'Sin Nombre']);
});

it('reimportar el mismo CSV actualiza en vez de duplicar (upsert)', function () {
    $user = userWithRole('secretaria');

    $csv = "first_name,last_name,phone,email,role,birth_date,joined_at,active,notes\n"
        ."Ana,García Pérez,600111222,ana@example.com,lobato,2016-04-01,2023-09-01,1,\n";

    $this->actingAs($user)->post(route('members.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('miembros.csv', $csv),
    ]);

    // Reimporta con teléfono cambiado: debe actualizar la ficha existente.
    $csv2 = "first_name,last_name,phone,email,role,birth_date,joined_at,active,notes\n"
        ."Ana,García Pérez,600555666,ana@example.com,lobato,2016-04-01,2023-09-01,1,\n";

    $this->actingAs($user)->post(route('members.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('miembros.csv', $csv2),
    ]);

    expect(Member::where('first_name', 'Ana')->where('last_name', 'García Pérez')->count())->toBe(1);
    expect(Member::where('first_name', 'Ana')->first()->phone)->toBe('600555666');
});

it('importa columnas de familia creando y vinculando la familia', function () {
    $user = userWithRole('secretaria');

    $csv = "first_name,last_name,phone,email,role,birth_date,joined_at,active,notes,family_name,family_phone,family_email\n"
        ."Ana,García Pérez,600111222,ana@example.com,lobato,2016-04-01,2023-09-01,1,,Familia García,600999888,garcia@example.com\n"
        ."Pablo,García Pérez,,,castor,2019-02-10,2024-09-01,1,,Familia García,600999888,garcia@example.com\n";

    $this->actingAs($user)->post(route('members.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('miembros.csv', $csv),
    ])->assertRedirect();

    // Una sola familia reutilizada para los dos hermanos.
    expect(Family::where('name', 'Familia García')->count())->toBe(1);

    $family = Family::where('name', 'Familia García')->first();
    expect($family->contact_phone)->toBe('600999888');
    expect($family->members()->count())->toBe(2);
});

it('familia no puede importar miembros', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('members.import'))->assertForbidden();
});
