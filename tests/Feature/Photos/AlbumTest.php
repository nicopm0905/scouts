<?php

use App\Models\Album;
use App\Models\Photo;
use App\Services\Drive\FakeDriveService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    FakeDriveService::reset();
});

it('secretaría puede crear un álbum con carpeta en Drive', function () {
    $user = userWithRole('secretaria');

    $response = $this->actingAs($user)->post(route('albums.store'), [
        'title' => 'Campamento de verano',
        'description' => 'Fotos del campamento',
        'visibility' => 'internal',
    ]);

    $response->assertRedirect();

    $album = Album::firstWhere('title', 'Campamento de verano');
    expect($album)->not->toBeNull()
        ->and($album->drive_folder_id)->not->toBeNull()
        ->and(FakeDriveService::$folders)->toHaveKey($album->drive_folder_id);
});

it('secretaría puede actualizar y eliminar un álbum', function () {
    $user = userWithRole('secretaria');
    $album = Album::factory()->create(['title' => 'Original']);

    $this->actingAs($user)->put(route('albums.update', $album), [
        'title' => 'Actualizado',
        'description' => null,
        'event_id' => null,
        'visibility' => 'publishable',
    ])->assertRedirect();

    expect($album->fresh()->title)->toBe('Actualizado')
        ->and($album->fresh()->visibility)->toBe('publishable');

    $this->actingAs($user)->delete(route('albums.destroy', $album))->assertRedirect();

    expect(Album::find($album->id))->toBeNull();
});

it('sube una foto a un álbum y la guarda en Drive', function () {
    $user = userWithRole('secretaria');
    $album = Album::factory()->create();

    $file = UploadedFile::fake()->image('foto.jpg');

    $response = $this->actingAs($user)->post(route('albums.photos.store', $album), [
        'file' => $file,
        'caption' => 'Un buen recuerdo',
    ]);

    $response->assertRedirect();

    $photo = Photo::firstWhere('album_id', $album->id);
    expect($photo)->not->toBeNull()
        ->and($photo->drive_file_id)->not->toBeNull()
        ->and(FakeDriveService::$files)->toHaveKey($photo->drive_file_id)
        ->and($photo->caption)->toBe('Un buen recuerdo');
});

it('un responsable de otra rama no puede gestionar álbumes sin el permiso photos.manage', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->post(route('albums.store'), [
        'title' => 'Intento',
        'visibility' => 'internal',
    ])->assertForbidden();
});
