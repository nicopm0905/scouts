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
}
