<?php

namespace App\Http\Controllers\Finance;

use App\Enums\BudgetStatus;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function show(Budget $budget)
    {
        $budget->load('items.invoices');

        return Inertia::render('Budgets/Show', [
            'budget' => $budget,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $event = Event::findOrFail($validated['event_id']);

        $budget = Budget::firstOrCreate(
            ['event_id' => $event->id],
            [
                'name' => 'Presupuesto: '.$event->title,
                'status' => BudgetStatus::Draft->value,
            ]
        );

        return redirect()->route('budgets.show', $budget)->with('success', 'Presupuesto creado.');
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(BudgetStatus::values())],
        ]);

        $budget->update($validated);

        return back()->with('success', 'Estado del presupuesto actualizado.');
    }

    public function storeItem(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
        ]);

        $budget->items()->create($validated);

        return back()->with('success', 'Línea de presupuesto añadida.');
    }

    public function updateItem(Request $request, BudgetItem $budgetItem)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $budgetItem->update($validated);

        return back()->with('success', 'Línea actualizada.');
    }

    public function destroyItem(BudgetItem $budgetItem)
    {
        if ($budgetItem->invoices()->exists()) {
            return back()->with('error', 'No se puede borrar porque tiene facturas asociadas.');
        }

        $budgetItem->delete();

        return back()->with('success', 'Línea eliminada.');
    }
}
