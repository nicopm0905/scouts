<?php

namespace App\Services\Finance;

use App\Enums\InvoiceDirection;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Drive\DriveServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * Genera el PDF de una factura/recibo emitido con los datos fiscales del grupo
 * (Setting::finance.group_*) y lo sube vía DriveService (nunca al disco local).
 */
class InvoicePdfService
{
    public function __construct(private DriveServiceInterface $drive)
    {
    }

    /** Asigna número de serie autonumérica si la factura emitida aún no tiene uno. */
    public function ensureNumber(Invoice $invoice): Invoice
    {
        if ($invoice->direction !== InvoiceDirection::Issued || $invoice->number) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice) {
            $year = $invoice->date?->format('Y') ?? now()->format('Y');
            $last = Invoice::where('direction', InvoiceDirection::Issued->value)
                ->where('number', 'like', "{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('number');

            $next = $last ? ((int) substr($last, strpos($last, '-') + 1)) + 1 : 1;
            $invoice->number = sprintf('%s-%04d', $year, $next);
            $invoice->save();

            return $invoice;
        });
    }

    /** Genera el PDF, lo sube a Drive y guarda el drive_file_id en la factura. */
    public function generate(Invoice $invoice): Invoice
    {
        $this->ensureNumber($invoice);

        $groupData = [
            'name' => Setting::get('finance.group_name', config('app.name')),
            'tax_id' => Setting::get('finance.group_tax_id'),
            'address' => Setting::get('finance.group_address'),
            'postal_code' => Setting::get('finance.group_postal_code'),
            'city' => Setting::get('finance.group_city'),
            'email' => Setting::get('finance.group_email'),
            'phone' => Setting::get('finance.group_phone'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'group' => $groupData,
        ]);

        $fileName = 'factura-'.($invoice->number ?? $invoice->id).'.pdf';
        $driveFile = $this->drive->uploadRaw($pdf->output(), $fileName, 'application/pdf');

        $invoice->update(['drive_file_id' => $driveFile->id]);

        return $invoice;
    }
}
