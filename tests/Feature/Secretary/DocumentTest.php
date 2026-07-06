<?php

use App\Models\Document;
use App\Services\Drive\FakeDriveService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    FakeDriveService::reset();
});

it('una secretaria puede crear, editar y eliminar un documento con subida a Drive', function () {
    $secretaria = userWithRole('secretaria');

    $response = $this->actingAs($secretaria)->post(route('documents.store'), [
        'title' => 'Seguro de responsabilidad civil',
        'category' => 'seguros',
        'file' => UploadedFile::fake()->create('seguro.pdf', 100, 'application/pdf'),
        'expires_at' => now()->addMonths(6)->format('Y-m-d'),
    ]);

    $response->assertRedirect();

    $document = Document::firstWhere('title', 'Seguro de responsabilidad civil');
    expect($document)->not->toBeNull()
        ->and($document->category->value)->toBe('seguros')
        ->and($document->drive_file_id)->not->toBeNull()
        ->and(FakeDriveService::$files)->toHaveKey($document->drive_file_id);

    $this->actingAs($secretaria)->put(route('documents.update', $document), [
        'title' => 'Seguro RC actualizado',
        'category' => 'seguros',
        'expires_at' => now()->addYear()->format('Y-m-d'),
    ])->assertRedirect();

    expect($document->fresh()->title)->toBe('Seguro RC actualizado');

    $this->actingAs($secretaria)->delete(route('documents.destroy', $document))->assertRedirect();

    expect(Document::find($document->id))->toBeNull();
});

it('permite registrar un documento con una URL externa en lugar de fichero', function () {
    $secretaria = userWithRole('secretaria');

    $response = $this->actingAs($secretaria)->post(route('documents.store'), [
        'title' => 'Estatutos del grupo',
        'category' => 'estatutos',
        'external_url' => 'https://example.com/estatutos.pdf',
    ]);

    $response->assertRedirect();

    $document = Document::firstWhere('title', 'Estatutos del grupo');
    expect($document)->not->toBeNull()
        ->and($document->external_url)->toBe('https://example.com/estatutos.pdf')
        ->and($document->drive_file_id)->toBeNull();
});

it('el índice muestra la alerta de documentos que caducan pronto', function () {
    $secretaria = userWithRole('secretaria');

    Document::factory()->create([
        'title' => 'Póliza a punto de caducar',
        'category' => 'seguros',
        'expires_at' => now()->addDays(10),
    ]);
    Document::factory()->create([
        'title' => 'Póliza lejana',
        'category' => 'seguros',
        'expires_at' => now()->addMonths(10),
    ]);

    $response = $this->actingAs($secretaria)->get(route('documents.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Documents/Index')
        ->has('expiring', 1)
        ->where('expiring.0.title', 'Póliza a punto de caducar')
    );
});

it('un responsable sin permiso documents.manage recibe 403 al crear un documento', function () {
    $responsable = userWithRole('responsable', ['lobato']);

    $response = $this->actingAs($responsable)->post(route('documents.store'), [
        'title' => 'Documento no autorizado',
        'category' => 'otro',
        'external_url' => 'https://example.com/doc.pdf',
    ]);

    $response->assertForbidden();
    expect(Document::firstWhere('title', 'Documento no autorizado'))->toBeNull();
});
