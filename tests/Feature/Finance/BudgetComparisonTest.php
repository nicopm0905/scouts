<?php

use App\Enums\EventType;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Services\Finance\FinanceReportService;

function budgetWithLines(Event $event): Budget
{
    $budget = Budget::create(['name' => 'Presupuesto: '.$event->title, 'event_id' => $event->id, 'status' => 'active']);

    $income = BudgetItem::create(['budget_id' => $budget->id, 'description' => 'Cuotas', 'type' => 'income', 'amount' => 500]);
    $expense = BudgetItem::create(['budget_id' => $budget->id, 'description' => 'Autobús', 'type' => 'expense', 'amount' => 300]);

    // Ingreso real 400 (< previsto 500). Gasto real 350 (> previsto 300).
    Invoice::factory()->issued()->create(['budget_item_id' => $income->id, 'amount' => 330.58, 'vat' => 69.42]);
    Invoice::factory()->create(['budget_item_id' => $expense->id, 'amount' => 289.26, 'vat' => 60.74]);

    return $budget;
}

it('Budget::summary calcula previsto, real y desvío del balance', function () {
    $event = Event::factory()->create(['type' => EventType::Acampada]);
    $s = budgetWithLines($event)->summary();

    expect($s['expected_income'])->toBe(500.0)
        ->and($s['expected_expense'])->toBe(300.0)
        ->and($s['real_income'])->toBe(400.0)
        ->and($s['real_expense'])->toBe(350.0)
        ->and($s['expected_balance'])->toBe(200.0)
        ->and($s['real_balance'])->toBe(50.0)
        ->and($s['variance'])->toBe(-150.0); // 50 real − 200 previsto
});

it('el informe económico incluye el presupuesto vs real de los eventos del periodo', function () {
    $inRange = Event::factory()->create(['type' => EventType::Acampada, 'title' => 'Acampada de otoño', 'start_at' => '2026-05-10 10:00:00']);
    $outOfRange = Event::factory()->create(['type' => EventType::Acampada, 'title' => 'Campamento verano', 'start_at' => '2027-07-10 10:00:00']);
    budgetWithLines($inRange);
    budgetWithLines($outOfRange);

    $report = app(FinanceReportService::class)->build('2026-01-01', '2026-12-31');

    expect($report['budgets'])->toHaveCount(1)
        ->and($report['budgets'][0]['event'])->toBe('Acampada de otoño')
        ->and($report['budgets'][0]['variance'])->toBe(-150.0);

    $csv = app(FinanceReportService::class)->toCsvRows($report);
    $flat = collect($csv)->map(fn ($r) => implode('|', $r))->implode("\n");
    expect($flat)->toContain('Presupuesto vs real')
        ->and($flat)->toContain('Acampada de otoño');
});

it('la ficha del presupuesto expone la comparación al frontend', function () {
    $user = userWithRole('tesoreria');
    $event = Event::factory()->create(['type' => EventType::Acampada]);
    $budget = budgetWithLines($event);

    $this->actingAs($user)->get(route('budgets.show', $budget))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Budgets/Show')
            ->where('comparison.variance', -150));
});
