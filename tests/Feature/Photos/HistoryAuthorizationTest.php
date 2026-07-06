<?php

use App\Models\HistoryEntry;

it('un responsable sin history.manage recibe 403 al editar la historia', function () {
    $user = userWithRole('responsable', ['lobato']);
    $entry = HistoryEntry::factory()->create();

    expect($user->can('update', $entry))->toBeFalse();

    $this->actingAs($user)->put(route('history.update', $entry), [
        'year' => $entry->year,
        'title' => 'Intento no autorizado',
        'published' => true,
    ])->assertForbidden();
});

it('un responsable sin history.manage recibe 403 al crear una entrada', function () {
    $user = userWithRole('responsable', ['lobato']);

    $this->actingAs($user)->post(route('history.store'), [
        'year' => 2020,
        'title' => 'Intento no autorizado',
        'published' => true,
    ])->assertForbidden();
});

it('cualquier usuario autenticado puede ver el listado de historia (lectura general)', function () {
    $user = userWithRole('responsable', ['lobato']);

    $this->actingAs($user)->get(route('history.index'))->assertOk();
});
