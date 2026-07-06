<?php

use App\Models\HistoryEntry;

it('secretaría puede crear, actualizar y eliminar entradas de historia', function () {
    $user = userWithRole('secretaria');

    $this->actingAs($user)->post(route('history.store'), [
        'year' => 1985,
        'title' => 'Fundación del grupo',
        'body' => 'Se funda el grupo scout.',
        'published' => true,
    ])->assertRedirect();

    $entry = HistoryEntry::firstWhere('title', 'Fundación del grupo');
    expect($entry)->not->toBeNull();

    $this->actingAs($user)->put(route('history.update', $entry), [
        'year' => 1986,
        'title' => 'Fundación del grupo (corregido)',
        'body' => 'Texto actualizado.',
        'published' => false,
    ])->assertRedirect();

    expect($entry->fresh()->year)->toBe(1986)
        ->and($entry->fresh()->published)->toBeFalse();

    $this->actingAs($user)->delete(route('history.destroy', $entry))->assertRedirect();

    expect(HistoryEntry::find($entry->id))->toBeNull();
});

it('lista las entradas de historia ordenadas por año para su gestión', function () {
    $user = userWithRole('secretaria');

    HistoryEntry::factory()->create(['year' => 2000, 'title' => 'Segunda']);
    HistoryEntry::factory()->create(['year' => 1990, 'title' => 'Primera']);

    $response = $this->actingAs($user)->get(route('history.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('History/Index')
        ->where('entries.0.title', 'Primera')
        ->where('entries.1.title', 'Segunda')
    );
});
