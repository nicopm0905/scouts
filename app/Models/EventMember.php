<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Inscripción de un miembro en un evento (pivote event_member con datos propios). */
class EventMember extends Model
{
    use HasFactory;

    protected $table = 'event_member';

    protected static function booted(): void
    {
        static::saved(function (EventMember $enrollment) {
            if ($enrollment->enrolled && ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('enrolled'))) {
                $charges = Charge::where('event_id', $enrollment->event_id)->get();
                foreach ($charges as $charge) {
                    $charge->assignments()->firstOrCreate(
                        ['member_id' => $enrollment->member_id],
                        ['amount' => $charge->amount, 'status' => 'pending']
                    );
                }
            }
        });
    }

    protected $fillable = [
        'event_id', 'member_id', 'enrolled', 'public_token',
        'confirmed_at', 'authorization_file_id', 'notes',
        'signature_data', 'medical_consent', 'image_consent',
        'family_name', 'family_dni', 'address', 'contact_phone', 'health_summary',
    ];

    protected $casts = [
        'enrolled' => 'boolean',
        'medical_consent' => 'boolean',
        'image_consent' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function hasAuthorization(): bool
    {
        return ! empty($this->authorization_file_id)
            || ! empty($this->signature_data)
            || ! empty($this->confirmed_at);
    }
}
