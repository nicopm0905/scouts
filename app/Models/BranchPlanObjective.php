<?php

namespace App\Models;

use App\Enums\MscScope;
use App\Enums\ObjectiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BranchPlanObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_plan_id', 'scope', 'line', 'development_area', 'content', 'current_situation',
        'goal_verb', 'goal_complement', 'description', 'term', 'status', 'evaluation', 'position',
    ];

    protected $casts = [
        'scope' => MscScope::class,
        'status' => ObjectiveStatus::class,
        'term' => 'integer',
    ];

    /**
     * El objetivo tal y como se lee en el impreso: "Mejorar la confianza entre
     * los miembros de la unidad". Se compone del verbo y su complemento; si el
     * objetivo se escribió antes de adoptar el formato MSC, cae en description.
     */
    public function goalText(): string
    {
        $composed = trim(($this->goal_verb ?? '').' '.($this->goal_complement ?? ''));

        return $composed !== '' ? $composed : (string) $this->description;
    }

    public function branchPlan(): BelongsTo
    {
        return $this->belongsTo(BranchPlan::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_objective');
    }
}
