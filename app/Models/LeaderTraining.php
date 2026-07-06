<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'leader_profile_id', 'name', 'obtained_at', 'expires_at', 'drive_file_id',
    ];

    protected $casts = [
        'obtained_at' => 'date',
        'expires_at' => 'date',
    ];

    public function leaderProfile(): BelongsTo
    {
        return $this->belongsTo(LeaderProfile::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
