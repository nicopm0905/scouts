<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class FinanceReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private FinanceReportService $service)
    {
    }

    public function index(Request $request): \Inertia\Response
    {
        $this->authorize('finance.reports');

        $report = $this->service->build($request->query('from'), $request->query('to'));

        return Inertia::render('Finance/Report', [
            'report' => $report,
            'filters' => [
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->authorize('finance.reports');

        $report = $this->service->build($request->query('from'), $request->query('to'));
        $rows = $this->service->toCsvRows($report);

        $csv = implode("\n", array_map(
            fn (array $row) => implode(';', array_map(fn ($v) => str_replace(';', ',', (string) $v), $row)),
            $rows
        ));

        $filename = "informe-economico-{$report['from']}_a_{$report['to']}.csv";

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
