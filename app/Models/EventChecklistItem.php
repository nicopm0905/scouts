<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'label', 'done', 'drive_file_id', 'notes', 'position',
    ];

    protected $casts = [
        'done' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
