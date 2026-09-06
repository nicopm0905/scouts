<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\ImportMembersRequest;
use App\Models\Member;
use App\Services\Members\MemberImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberImportController extends Controller
{
    public function __construct(private readonly MemberImportService $importService) {}

    public function create(): Response
    {
        Gate::authorize('create', Member::class);

        return Inertia::render('Members/Import');
    }

    public function store(ImportMembersRequest $request): RedirectResponse
    {
        $result = $this->importService->import($request->file('file'));

        $counts = "{$result['created']} creados, {$result['updated']} actualizados";

        if (! empty($result['errors'])) {
            $summary = collect($result['errors'])
                ->map(fn ($e) => "Fila {$e['row']}: ".implode(' ', $e['errors']))
                ->implode(' | ');

            return back()->with('error', "Importados ({$counts}). Errores: {$summary}");
        }

        return redirect()->route('members.index')->with('success', "Importación completada: {$counts}.");
    }

    public function template(): HttpResponse
    {
        Gate::authorize('create', Member::class);

        return response($this->importService->templateExcel(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="plantilla_miembros.xlsx"',
        ]);
    }
}
