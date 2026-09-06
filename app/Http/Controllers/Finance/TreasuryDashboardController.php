<?php

namespace App\Http\Controllers\Finance;

use App\Enums\InvoiceDirection;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreasuryDashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Facturas entregadas vs pendientes
        $invoices = Invoice::with('budgetItem')->get();
        $pendingInvoicesCount = $invoices->where('status', InvoiceStatus::Draft)->count();
        $submittedInvoicesCount = $invoices->where('status', InvoiceStatus::Submitted)->count();

        // 2. Gastos/Ingresos del mes actual
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $monthlyInvoices = Invoice::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $monthlyIncome = $monthlyInvoices->where('direction', InvoiceDirection::Issued)->sum(fn ($i) => (float) $i->total);
        $monthlyExpense = $monthlyInvoices->where('direction', InvoiceDirection::Received)->sum(fn ($i) => (float) $i->total);

        // 3. Presupuestos y estado
        $budgets = Budget::with('items.invoices')->get()->map(function ($budget) {
            $totalExpectedIncome = $budget->items->where('type', 'income')->sum('amount');
            $totalExpectedExpense = $budget->items->where('type', 'expense')->sum('amount');

            $realIncome = 0;
            $realExpense = 0;
            foreach ($budget->items as $item) {
                if ($item->type === 'income') {
                    $realIncome += $item->invoices->sum(fn ($i) => (float) $i->total);
                } else {
                    $realExpense += $item->invoices->sum(fn ($i) => (float) $i->total);
                }
            }

            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'expected_income' => $totalExpectedIncome,
                'expected_expense' => $totalExpectedExpense,
                'real_income' => $realIncome,
                'real_expense' => $realExpense,
            ];
        });

        // 4. Saldo total aproximado (simulado o calculado)
        $allTimeIncome = Invoice::where('direction', 'issued')->sum('amount') + Invoice::where('direction', 'issued')->sum('vat');
        $allTimeExpense = Invoice::where('direction', 'received')->sum('amount') + Invoice::where('direction', 'received')->sum('vat');
        $totalBalance = $allTimeIncome - $allTimeExpense;

        // 5. Datos para el gráfico de los últimos 6 meses
        $chartData = [
            'labels' => [],
            'income' => [],
            'expense' => [],
        ];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartData['labels'][] = $month->translatedFormat('M Y');

            $monthInvoices = Invoice::whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->get();

            $chartData['income'][] = $monthInvoices->where('direction', InvoiceDirection::Issued)->sum(fn ($i) => (float) $i->total);
            $chartData['expense'][] = $monthInvoices->where('direction', InvoiceDirection::Received)->sum(fn ($i) => (float) $i->total);
        }

        return Inertia::render('Finance/Dashboard', [
            'stats' => [
                'pending_invoices' => $pendingInvoicesCount,
                'submitted_invoices' => $submittedInvoicesCount,
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
                'total_balance' => $totalBalance,
            ],
            'budgets' => $budgets,
            'chartData' => $chartData,
        ]);
    }
}
