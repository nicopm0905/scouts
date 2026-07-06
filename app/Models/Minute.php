<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Minute extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'type', 'held_on', 'location', 'drive_file_id', 'created_by',
    ];

    protected $casts = [
        'held_on' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MinuteItem::class)->orderBy('position');
    }

    /** Asistentes (miembros responsables). */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'minute_member');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
