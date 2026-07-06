<?php

use Illuminate\Http\UploadedFile;

it('permite descargar la plantilla CSV', function () {
    $user = userWithRole('secretaria');

    $response = $this->actingAs($user)->get(route('members.import.template'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->getContent())->toContain('first_name,last_name,phone,email,role,birth_date,joined_at,active,notes');
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

it('familia no puede importar miembros', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('members.import'))->assertForbidden();
});
