<?php

namespace App\Models;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Enums\InvoiceStatus;
use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'direction',
        'number',
        'date',
        'supplier_or_client',
        'concept',
        'amount',
        'vat',
        'category',
        'branch',
        'status',
        'budget_item_id',
        'drive_file_id',
        'created_by',
        'submitted_at',
    ];

    protected $casts = [
        'direction' => InvoiceDirection::class,
        'category' => InvoiceCategory::class,
        'status' => InvoiceStatus::class,
        'branch' => MemberRole::class,
        'date' => 'date',
        'amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    /** Trazabilidad: alta/cambio de facturas. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoice')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function getTotalAttribute(): string
    {
        return number_format((float) $this->amount + (float) $this->vat, 2, '.', '');
    }
}
