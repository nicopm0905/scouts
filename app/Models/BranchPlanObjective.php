<?php

namespace App\Models;

use App\Enums\ObjectiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BranchPlanObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_plan_id', 'description', 'term', 'status', 'position',
    ];

    protected $casts = [
        'status' => ObjectiveStatus::class,
        'term' => 'integer',
    ];

    public function branchPlan(): BelongsTo
    {
        return $this->belongsTo(BranchPlan::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_objective');
    }
}
