<?php

use App\Models\Album;
use App\Models\Photo;

it('la galería pública solo lista álbumes publicables', function () {
    $publicAlbum = Album::factory()->publishable()->create(['title' => 'Álbum público']);
    Photo::factory()->create(['album_id' => $publicAlbum->id]);

    $internalAlbum = Album::factory()->create(['title' => 'Álbum interno', 'visibility' => 'internal']);
    Photo::factory()->create(['album_id' => $internalAlbum->id]);

    $response = $this->get(route('albums.public'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Albums/Public')
        ->has('albums', 1)
        ->where('albums.0.title', 'Álbum público')
    );
});

it('la galería interna (índice de álbumes) muestra todos los álbumes a quien tiene photos.view', function () {
    $user = userWithRole('secretaria');

    Album::factory()->publishable()->create();
    Album::factory()->create(['visibility' => 'internal']);

    $response = $this->actingAs($user)->get(route('albums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Albums/Index')
        ->has('albums', 2)
    );
});
