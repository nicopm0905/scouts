<?php

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Models\Invoice;
use App\Services\Drive\FakeDriveService;

beforeEach(fn () => FakeDriveService::reset());

it('genera el PDF de una factura emitida con numeración autonumérica y lo sube a Drive', function () {
    $user = userWithRole('tesoreria');

    $response = $this->actingAs($user)->post(route('invoices.store'), [
        'direction' => InvoiceDirection::Issued->value,
        'date' => now()->toDateString(),
        'supplier_or_client' => 'Familia Pérez',
        'concept' => 'Cuota campamento de verano',
        'amount' => 150,
        'vat' => 0,
        'category' => InvoiceCategory::Otro->value,
    ]);

    $response->assertSessionHasNoErrors();

    $invoice = Invoice::firstWhere('supplier_or_client', 'Familia Pérez');

    expect($invoice)->not->toBeNull()
        ->and($invoice->number)->not->toBeNull()
        ->and($invoice->drive_file_id)->not->toBeNull();

    expect(FakeDriveService::$files)->toHaveKey($invoice->drive_file_id);
    expect(FakeDriveService::$files[$invoice->drive_file_id]['contents'])->toContain('%PDF');
});

it('el número de factura emitida es autonumérico secuencial por año', function () {
    $user = userWithRole('tesoreria');

    foreach (range(1, 2) as $i) {
        $this->actingAs($user)->post(route('invoices.store'), [
            'direction' => InvoiceDirection::Issued->value,
            'date' => now()->toDateString(),
            'supplier_or_client' => "Cliente {$i}",
            'concept' => 'Recibo',
            'amount' => 20,
            'vat' => 0,
            'category' => InvoiceCategory::Otro->value,
        ]);
    }

    $numbers = Invoice::orderBy('id')->pluck('number')->all();

    expect($numbers)->toHaveCount(2);
    [$first, $second] = $numbers;
    $firstSeq = (int) substr($first, strpos($first, '-') + 1);
    $secondSeq = (int) substr($second, strpos($second, '-') + 1);

    expect($secondSeq)->toBe($firstSeq + 1);
});
