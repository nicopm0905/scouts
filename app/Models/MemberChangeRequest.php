<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Solicitud de revisión de datos enviada por una familia desde el portal
 * (revisión puntual o campaña de inicio de curso). Secretaría la revisa y, al
 * aprobarla, se escriben los cambios en el miembro, su ficha sanitaria, los
 * datos de su familia y sus consentimientos (con la trazabilidad RGPD que ya
 * llevan esos modelos).
 */
class MemberChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'submitted_by', 'status', 'payload', 'note',
        'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'status' => ChangeRequestStatus::class,
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Campos que una familia puede proponer, por grupo. El grupo `health` toca
     * `health_records` (categoría especial RGPD): solo se aplica si el revisor
     * tiene el permiso `members.sensitive`.
     *
     * @return array<string, list<string>>
     */
    public static function editableFields(): array
    {
        return [
            'member' => ['phone', 'email'],
            'health' => ['allergies', 'intolerances', 'medication', 'observations'],
            'family' => ['contact_phone', 'contact_email'],
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ChangeRequestStatus::Pending->value);
    }

    /**
     * Aplica los cambios propuestos y marca la solicitud como aprobada.
     * `$canHealth` = el revisor tiene members.sensitive.
     */
    public function apply(User $reviewer, bool $canHealth): void
    {
        $fields = self::editableFields();
        $member = $this->member;

        DB::transaction(function () use ($reviewer, $canHealth, $fields, $member) {
            // 1. Datos del miembro.
            $memberChanges = array_intersect_key($this->payload['member'] ?? [], array_flip($fields['member']));
            if ($memberChanges) {
                $member->fill($memberChanges)->save();
            }

            // 2. Ficha sanitaria (solo con members.sensitive).
            $healthChanges = $canHealth
                ? array_intersect_key($this->payload['health'] ?? [], array_flip($fields['health']))
                : [];
            if ($healthChanges) {
                $record = $member->healthRecord()->firstOrNew([]);
                $record->fill($healthChanges);
                $member->healthRecord()->save($record);
            }

            // 3. Datos de contacto de la familia vinculada a la cuenta que envió la revisión.
            $familyChanges = array_intersect_key($this->payload['family'] ?? [], array_flip($fields['family']));
            if ($familyChanges) {
                $member->families()
                    ->when($this->submitted_by, fn ($q) => $q->whereHas('users', fn ($u) => $u->whereKey($this->submitted_by)))
                    ->get()
                    ->each(fn (Family $family) => $family->fill($familyChanges)->save());
            }

            // 4. Consentimientos (RGPD, imagen, salidas periódicas).
            foreach ($this->payload['consents'] ?? [] as $type => $granted) {
                if (! in_array($type, ConsentType::values(), true)) {
                    continue;
                }
                $member->consents()->updateOrCreate(
                    ['type' => $type],
                    ['granted' => (bool) $granted, 'signed_at' => now()->toDateString()]
                );
            }

            // 5. Renovación de plaza: si la familia dice que NO continúa, se da de baja.
            $renewal = $this->payload['renewal'] ?? null;
            if (is_array($renewal) && array_key_exists('continues', $renewal) && $renewal['continues'] === false) {
                $member->update(['active' => false]);
            }

            $this->update([
                'status' => ChangeRequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(User $reviewer, ?string $note = null): void
    {
        $this->update([
            'status' => ChangeRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
