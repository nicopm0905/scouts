<?php

use App\Models\HistoryEntry;

it('la página pública de historia solo muestra entradas publicadas, ordenadas por año', function () {
    HistoryEntry::factory()->create(['year' => 2010, 'title' => 'Publicada reciente', 'published' => true]);
    HistoryEntry::factory()->create(['year' => 1990, 'title' => 'Publicada antigua', 'published' => true]);
    HistoryEntry::factory()->create(['year' => 2000, 'title' => 'Borrador', 'published' => false]);

    $response = $this->get(route('history.public'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Public/History/Show')
        ->has('entries', 2)
        ->where('entries.0.title', 'Publicada antigua')
        ->where('entries.1.title', 'Publicada reciente')
    );
});

it('la página pública de historia no requiere autenticación', function () {
    $this->get(route('history.public'))->assertOk();
});
