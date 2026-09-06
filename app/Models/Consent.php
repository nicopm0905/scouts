<?php

namespace App\Models;

use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Consent extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'member_id', 'type', 'granted', 'signed_at', 'drive_file_id',
    ];

    protected $casts = [
        'type' => ConsentType::class,
        'granted' => 'boolean',
        'signed_at' => 'date',
    ];

    /** Trazabilidad RGPD: alta/cambio/revocación de consentimientos. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('consent')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function signature(): MorphOne
    {
        return $this->morphOne(Signature::class, 'signable');
    }
}
