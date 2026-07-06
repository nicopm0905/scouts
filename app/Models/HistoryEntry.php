<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'title', 'body', 'photo_file_id', 'position', 'published',
    ];

    protected $casts = [
        'year' => 'integer',
        'published' => 'boolean',
    ];
}
