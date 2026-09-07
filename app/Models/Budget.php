<?php

namespace App\Models;

use App\Enums\BudgetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'event_id',
        'status',
    ];

    protected $casts = [
        'status' => BudgetStatus::class,
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * Presupuestado vs. real (facturas asociadas a las partidas) y su desvío.
     *
     * @return array{expected_income:float, expected_expense:float, real_income:float, real_expense:float, expected_balance:float, real_balance:float, variance:float}
     */
    public function summary(): array
    {
        $this->loadMissing('items.invoices');

        $expected = fn (string $type) => round((float) $this->items->where('type', $type)->sum('amount'), 2);
        $real = fn (string $type) => round((float) $this->items->where('type', $type)
            ->flatMap->invoices
            ->sum(fn (Invoice $i) => (float) $i->amount + (float) $i->vat), 2);

        $expectedIncome = $expected('income');
        $expectedExpense = $expected('expense');
        $realIncome = $real('income');
        $realExpense = $real('expense');

        return [
            'expected_income' => $expectedIncome,
            'expected_expense' => $expectedExpense,
            'real_income' => $realIncome,
            'real_expense' => $realExpense,
            'expected_balance' => round($expectedIncome - $expectedExpense, 2),
            'real_balance' => round($realIncome - $realExpense, 2),
            // Desvío del balance: real − previsto (positivo = mejor de lo previsto).
            'variance' => round(($realIncome - $realExpense) - ($expectedIncome - $expectedExpense), 2),
        ];
    }
}
