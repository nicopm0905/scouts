<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'description',
        'type',
        'amount',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
