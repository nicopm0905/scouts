<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'label', 'done', 'assigned_to', 'due_at', 'drive_file_id', 'notes', 'position',
    ];

    protected $casts = [
        'done' => 'boolean',
        'due_at' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
