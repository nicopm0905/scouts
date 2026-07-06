<?php

namespace App\Models;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch', 'school_year', 'description',
    ];

    protected $casts = [
        'branch' => MemberRole::class,
    ];

    public function objectives(): HasMany
    {
        return $this->hasMany(BranchPlanObjective::class)->orderBy('position');
    }

    /** % de objetivos logrados (para la memoria anual). */
    public function completionPercentage(): int
    {
        $total = $this->objectives()->count();
        if ($total === 0) {
            return 0;
        }

        $done = $this->objectives()->where('status', \App\Enums\ObjectiveStatus::Logrado->value)->count();

        return (int) round($done / $total * 100);
    }
}
