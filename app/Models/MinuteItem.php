<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinuteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'minute_id', 'position', 'topic', 'discussion', 'agreement',
    ];

    public function minute(): BelongsTo
    {
        return $this->belongsTo(Minute::class);
    }
}
