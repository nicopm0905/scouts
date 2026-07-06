<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'branch', 'duration_minutes', 'objectives_text',
        'development', 'attachment_file_ids', 'created_by',
    ];

    protected $casts = [
        'attachment_file_ids' => 'array',
        'duration_minutes' => 'integer',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(ActivityMaterial::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(BranchPlanObjective::class, 'activity_objective');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
