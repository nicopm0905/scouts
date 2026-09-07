<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BranchPlan;
use App\Services\Reports\AnnualReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Memoria del curso (cierre de curso). Un responsable solo ve sus ramas;
 * coordinación/secretaría ven el grupo entero.
 */
class AnnualReportController extends Controller
{
    public function __construct(private readonly AnnualReportService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BranchPlan::class);

        $year = $this->resolveYear($request);
        $branches = $this->branchScope($request);

        return Inertia::render('Reports/AnnualReport', [
            'report' => $this->service->build($year, $branches),
            'year' => $year,
            'years' => $this->availableYears(),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        Gate::authorize('viewAny', BranchPlan::class);

        $year = $this->resolveYear($request);
        $report = $this->service->build($year, $this->branchScope($request));

        return Pdf::loadView('pdf.annual-report', ['report' => $report])
            ->setPaper('a4')
            ->stream("memoria-{$year}.pdf");
    }

    private function resolveYear(Request $request): string
    {
        $year = (string) $request->query('year', '');

        if (preg_match('/^\d{4}-\d{4}$/', $year)) {
            return $year;
        }

        return $this->availableYears()[0] ?? $this->currentSchoolYear();
    }

    /** @return list<string> */
    private function availableYears(): array
    {
        $fromPlans = BranchPlan::query()->distinct()->orderByDesc('school_year')->pluck('school_year')->all();
        $current = $this->currentSchoolYear();

        return collect([$current, ...$fromPlans])->unique()->values()->all();
    }

    private function currentSchoolYear(): string
    {
        $now = Carbon::now();
        $start = $now->month >= 9 ? $now->year : $now->year - 1;

        return $start.'-'.($start + 1);
    }

    /** @return list<string>|null null = todas las ramas */
    private function branchScope(Request $request): ?array
    {
        $user = $request->user();

        return $user->canSeeAllBranches() ? null : ($user->branches ?: ['__none__']);
    }
}
