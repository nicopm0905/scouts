<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Petición de firma digital simple (por email, sin login) de un Consent o Document
 * para un miembro concreto. Guarda el rastro de auditoría de la firma.
 */
class Signature extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'signable_type', 'signable_id', 'member_id', 'public_token', 'status',
        'sent_at', 'signed_at', 'expires_at',
        'signer_name', 'signature_image', 'signer_ip', 'signer_user_agent',
        'source_document_hash', 'signed_drive_file_id', 'created_by',
    ];

    protected $casts = [
        'status' => SignatureStatus::class,
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** Trazabilidad RGPD: envío y firma de documentos por las familias. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('signature')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSigned(): bool
    {
        return $this->status === SignatureStatus::Signed;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Título legible del documento a firmar, sea un Consent o un Document. */
    public function title(): string
    {
        return $this->signable instanceof Consent
            ? $this->signable->type->label()
            : $this->signable->title;
    }
}
