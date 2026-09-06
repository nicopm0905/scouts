<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Fila de reparto de un cobro a un miembro (tabla pivote charge_member con estado propio). */
class ChargeMember extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'charge_member';

    protected $fillable = [
        'charge_id', 'member_id', 'amount', 'status',
        'paid_at', 'payment_method', 'notes', 'reminder_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => ChargeStatus::class,
        'payment_method' => PaymentMethod::class,
        'paid_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /** Trazabilidad: cambios de estado de pago (incluye markPaid). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('charge_member')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function markPaid(?PaymentMethod $method = null): void
    {
        $this->update([
            'status' => ChargeStatus::Paid,
            'paid_at' => now(),
            'payment_method' => $method ?? $this->payment_method,
        ]);
    }
}
