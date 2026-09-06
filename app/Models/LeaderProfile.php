<?php

namespace App\Models;

use App\Enums\LeaderQualification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaderProfile extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'member_id', 'qualification',
        'sexual_offenses_certificate_date', 'sexual_offenses_certificate_expires_at',
        'branches',
    ];

    protected $casts = [
        'qualification' => LeaderQualification::class,
        'sexual_offenses_certificate_date' => 'date',
        'sexual_offenses_certificate_expires_at' => 'date',
        'branches' => 'array',
    ];

    /** Trazabilidad RGPD: certificados y cualificación de responsables. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leader_profile')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(LeaderTraining::class);
    }

    /** ¿El certificado de delitos sexuales está vigente? */
    public function hasValidSexualOffensesCertificate(): bool
    {
        if (! $this->sexual_offenses_certificate_date) {
            return false;
        }

        return $this->sexual_offenses_certificate_expires_at === null
            || $this->sexual_offenses_certificate_expires_at->isFuture();
    }

    public function isDirector(): bool
    {
        return $this->qualification === LeaderQualification::Director;
    }

    public function isInTraining(): bool
    {
        return $this->qualification === LeaderQualification::InTraining;
    }
}
