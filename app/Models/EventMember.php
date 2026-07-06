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

    protected $fillable = [
        'event_id', 'member_id', 'enrolled', 'public_token',
        'confirmed_at', 'authorization_file_id', 'notes',
    ];

    protected $casts = [
        'enrolled' => 'boolean',
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
        return ! empty($this->authorization_file_id);
    }
}
