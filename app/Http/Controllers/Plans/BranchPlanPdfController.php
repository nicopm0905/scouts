<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Models\BranchPlan;
use App\Services\Plans\TermPlanSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BranchPlanPdfController extends Controller
{
    /** Hoja de programación del trimestre en el formato de la delegación. */
    public function term(BranchPlan $branchPlan, int $term, TermPlanSheet $sheets): Response
    {
        Gate::authorize('view', $branchPlan);

        abort_unless(in_array($term, [1, 2, 3], true), 404);

        $pdf = Pdf::loadView('pdf.branch-plan-term', [
            'sheet' => $sheets->for($branchPlan, $term),
        ])->setPaper('a4', 'landscape');

        $filename = 'plan-'.Str::slug($branchPlan->branch->label())
            .'-'.$branchPlan->school_year.'-trimestre-'.$term.'.pdf';

        return $pdf->stream($filename);
    }
}
