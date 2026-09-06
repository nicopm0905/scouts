<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Solicitud de revisión de datos enviada por una familia desde el portal.
 * Secretaría la revisa y, al aprobarla, se escriben los cambios en el miembro
 * y su ficha sanitaria (con la trazabilidad RGPD que ya llevan esos modelos).
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
     * Aplica los cambios propuestos al miembro y (si procede) a su ficha sanitaria,
     * y marca la solicitud como aprobada. `$canHealth` = el revisor tiene members.sensitive.
     */
    public function apply(User $reviewer, bool $canHealth): void
    {
        $fields = self::editableFields();

        DB::transaction(function () use ($reviewer, $canHealth, $fields) {
            $memberChanges = array_intersect_key(
                $this->payload['member'] ?? [],
                array_flip($fields['member'])
            );
            if ($memberChanges) {
                $this->member->fill($memberChanges)->save();
            }

            $healthChanges = $canHealth
                ? array_intersect_key($this->payload['health'] ?? [], array_flip($fields['health']))
                : [];
            if ($healthChanges) {
                $record = $this->member->healthRecord()->firstOrNew([]);
                $record->fill($healthChanges);
                $this->member->healthRecord()->save($record);
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
