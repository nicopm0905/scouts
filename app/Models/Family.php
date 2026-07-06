<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'contact_phone', 'contact_email', 'notes',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)
            ->withPivot('relationship')
            ->withTimestamps();
    }
}
