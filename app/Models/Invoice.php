<?php

namespace App\Models;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction', 'number', 'date', 'supplier_or_client', 'concept',
        'amount', 'vat', 'category', 'drive_file_id', 'created_by',
    ];

    protected $casts = [
        'direction' => InvoiceDirection::class,
        'category' => InvoiceCategory::class,
        'date' => 'date',
        'amount' => 'decimal:2',
        'vat' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalAttribute(): string
    {
        return number_format((float) $this->amount + (float) $this->vat, 2, '.', '');
    }
}
