<?php

namespace App\Services\Finance;

use App\Enums\ChargeStatus;
use App\Models\ChargeMember;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Informe económico: ingresos y gastos agrupados por categoría y periodo.
 * Ingresos = cuotas/cobros ya pagados + facturas emitidas.
 * Gastos = facturas recibidas.
 */
class FinanceReportService
{
    /**
     * @return array{income: Collection, expense: Collection, total_income: float, total_expense: float, balance: float, rows: array}
     */
    public function build(?string $from, ?string $to): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : now()->startOfYear();
        $to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $paidCharges = ChargeMember::query()
            ->with(['charge', 'member'])
            ->where('status', ChargeStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->get();

        $issuedInvoices = Invoice::query()
            ->where('direction', 'issued')
            ->whereBetween('date', [$from, $to])
            ->get();

        $receivedInvoices = Invoice::query()
            ->where('direction', 'received')
            ->whereBetween('date', [$from, $to])
            ->get();

        $incomeByCategory = collect();

        foreach ($paidCharges->groupBy(fn (ChargeMember $cm) => $cm->charge->type->value) as $type => $items) {
            $incomeByCategory->push([
                'category' => $items->first()->charge->type->label(),
                'amount' => round((float) $items->sum('amount'), 2),
                'source' => 'Cobros',
            ]);
        }

        foreach ($issuedInvoices->groupBy(fn (Invoice $i) => $i->category->value) as $category => $items) {
            $incomeByCategory->push([
                'category' => $items->first()->category->label(),
                'amount' => round((float) $items->sum(fn (Invoice $i) => (float) $i->amount + (float) $i->vat), 2),
                'source' => 'Facturas emitidas',
            ]);
        }

        $expenseByCategory = collect();

        foreach ($receivedInvoices->groupBy(fn (Invoice $i) => $i->category->value) as $category => $items) {
            $expenseByCategory->push([
                'category' => $items->first()->category->label(),
                'amount' => round((float) $items->sum(fn (Invoice $i) => (float) $i->amount + (float) $i->vat), 2),
                'source' => 'Facturas recibidas',
            ]);
        }

        $totalIncome = round((float) $incomeByCategory->sum('amount'), 2);
        $totalExpense = round((float) $expenseByCategory->sum('amount'), 2);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'income' => $incomeByCategory->values(),
            'expense' => $expenseByCategory->values(),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /** Filas planas (ingreso/gasto, categoría, importe) listas para exportar a CSV. */
    public function toCsvRows(array $report): array
    {
        $rows = [['Tipo', 'Origen', 'Categoría', 'Importe']];

        foreach ($report['income'] as $row) {
            $rows[] = ['Ingreso', $row['source'], $row['category'], number_format($row['amount'], 2, '.', '')];
        }

        foreach ($report['expense'] as $row) {
            $rows[] = ['Gasto', $row['source'], $row['category'], number_format($row['amount'], 2, '.', '')];
        }

        $rows[] = [];
        $rows[] = ['Total ingresos', '', '', number_format($report['total_income'], 2, '.', '')];
        $rows[] = ['Total gastos', '', '', number_format($report['total_expense'], 2, '.', '')];
        $rows[] = ['Balance', '', '', number_format($report['balance'], 2, '.', '')];

        return $rows;
    }
}
