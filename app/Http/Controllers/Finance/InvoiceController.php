<?php

namespace App\Http\Controllers\Finance;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreInvoiceRequest;
use App\Http\Requests\Finance\StoreInvoiceUploadRequest;
use App\Http\Requests\Finance\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Finance\InvoicePdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private DriveServiceInterface $drive,
        private InvoicePdfService $pdfService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $direction = $request->string('direction')->toString() ?: null;
        $branch = $request->string('branch')->toString() ?: null;

        $invoices = Invoice::query()
            ->when($direction, fn ($q) => $q->where('direction', $direction))
            ->when($branch, fn ($q) => $q->where('branch', $branch))
            ->latest('date')
            ->get()
            ->map(fn (Invoice $invoice) => $this->toArray($invoice));

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'direction' => $direction,
            'branch' => $branch,
            'categories' => array_map(fn (InvoiceCategory $c) => ['value' => $c->value, 'label' => $c->label()], InvoiceCategory::cases()),
            'directions' => array_map(fn (InvoiceDirection $d) => ['value' => $d->value, 'label' => $d->label()], InvoiceDirection::cases()),
            'branches' => array_map(fn (MemberRole $r) => ['value' => $r->value, 'label' => $r->label()], MemberRole::branches()),
        ]);
    }

    /** Subida masiva drag&drop: crea una factura "borrador" por cada fichero subido a Drive. */
    public function upload(StoreInvoiceUploadRequest $request): RedirectResponse
    {
        $created = 0;

        foreach ($request->file('files', []) as $file) {
            $driveFile = $this->drive->upload($file);

            Invoice::create([
                'direction' => $request->input('direction'),
                'number' => null,
                'date' => now()->toDateString(),
                'supplier_or_client' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'concept' => 'Pendiente de completar',
                'amount' => 0,
                'vat' => 0,
                'category' => InvoiceCategory::Otro->value,
                'drive_file_id' => $driveFile->id,
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return back()->with('success', "{$created} factura(s) subida(s). Completa los datos en la tabla.");
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = Invoice::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        if ($invoice->direction === InvoiceDirection::Issued) {
            $this->pdfService->generate($invoice);
        }

        return back()->with('success', 'Factura creada.');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validated());

        return back()->with('success', 'Factura actualizada.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->drive_file_id) {
            $this->drive->delete($invoice->drive_file_id);
        }

        $invoice->delete();

        return back()->with('success', 'Factura eliminada.');
    }

    /** Genera (o regenera) el PDF de una factura emitida con los datos fiscales del grupo. */
    public function generatePdf(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->direction !== InvoiceDirection::Issued) {
            return back()->with('error', 'Solo se genera PDF para facturas emitidas.');
        }

        $this->pdfService->generate($invoice);

        return back()->with('success', 'PDF generado y guardado en Drive.');
    }

    private function toArray(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'direction' => $invoice->direction->value,
            'direction_label' => $invoice->direction->label(),
            'number' => $invoice->number,
            'date' => $invoice->date?->toDateString(),
            'supplier_or_client' => $invoice->supplier_or_client,
            'concept' => $invoice->concept,
            'amount' => (float) $invoice->amount,
            'vat' => (float) $invoice->vat,
            'total' => (float) $invoice->amount + (float) $invoice->vat,
            'category' => $invoice->category->value,
            'category_label' => $invoice->category->label(),
            'branch' => $invoice->branch?->value,
            'branch_label' => $invoice->branch?->label(),
            'drive_file_id' => $invoice->drive_file_id,
            'view_url' => $invoice->drive_file_id ? $this->drive->webViewLink($invoice->drive_file_id) : null,
        ];
    }
}
