<?php

use App\Models\Album;
use App\Models\HistoryEntry;
use App\Models\Photo;
use App\Services\Drive\DriveServiceInterface;

it('muestra la portada pública sin autenticación', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Home')
            ->where('group.name', config('group.name'))
            ->has('group.sections', 5)
            ->has('gallery')
            ->has('milestones')
        );
});

it('incluye en la portada fotos de álbumes publicables y no de los privados', function () {
    $drive = app(DriveServiceInterface::class);

    $publico = Album::factory()->create(['visibility' => 'publishable', 'title' => 'Campamento']);
    $fotoPublica = $drive->uploadRaw('foto', 'publica.jpg', 'image/jpeg');
    Photo::factory()->create(['album_id' => $publico->id, 'drive_file_id' => $fotoPublica->id]);

    $privado = Album::factory()->create(['visibility' => 'internal', 'title' => 'Interno']);
    $fotoPrivada = $drive->uploadRaw('foto', 'privada.jpg', 'image/jpeg');
    Photo::factory()->create(['album_id' => $privado->id, 'drive_file_id' => $fotoPrivada->id]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Home')
            ->where('gallery.0.album', 'Campamento')
            ->has('gallery', 1)
        );
});

it('muestra en la portada solo los hitos publicados de la historia', function () {
    HistoryEntry::factory()->create(['published' => true, 'year' => 1975, 'title' => 'Fundación del grupo']);
    HistoryEntry::factory()->create(['published' => false, 'year' => 1990, 'title' => 'Borrador']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('milestones', 1)
            ->where('milestones.0.title', 'Fundación del grupo')
        );
});

it('sirve la galería y la historia públicas sin autenticación', function () {
    $this->get('/galeria')->assertOk()->assertInertia(fn ($page) => $page->component('Albums/Public'));
    $this->get('/historia')->assertOk()->assertInertia(fn ($page) => $page->component('Public/History/Show'));
});

it('mantiene la presentación de la plataforma en /plataforma', function () {
    $this->get('/plataforma')->assertOk()->assertInertia(fn ($page) => $page->component('Welcome'));
});

it('ignora las fotos en formatos que el navegador no puede mostrar (HEIC renombrado a .jpg)', function () {
    $directorio = public_path('images/landing');
    $heic = $directorio.'/test-heic-renombrado.jpg';
    $jpeg = $directorio.'/test-jpeg-valido.jpg';

    // Cabecera HEIC real y cabecera JPEG real, con relleno para superar los 12 bytes.
    file_put_contents($heic, hex2bin('00000020').'ftypheic'.str_repeat('A', 32));
    file_put_contents($jpeg, hex2bin('FFD8FFE0').str_repeat('A', 32));

    config([
        'group.photos.hero' => 'test-heic-renombrado.jpg',
        'group.photos.about' => 'test-jpeg-valido.jpg',
    ]);

    try {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('group.photos.hero', null)
                ->where('group.photos.about', '/images/landing/test-jpeg-valido.jpg')
            );
    } finally {
        @unlink($heic);
        @unlink($jpeg);
    }
});
