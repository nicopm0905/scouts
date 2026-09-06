<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'title', 'category', 'drive_file_id', 'external_url',
        'expires_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'category' => DocumentCategory::class,
        'expires_at' => 'date',
    ];

    /** Trazabilidad: alta/cambio de documentos oficiales. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Documentos que caducan dentro de los próximos $days días. */
    public function scopeExpiringWithin(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}
