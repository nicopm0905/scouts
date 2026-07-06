<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Programación de actividades en eventos y vínculo con objetivos del plan de rama. */
return new class extends Migration
{
    public function up(): void
    {
        // Una actividad programada en un evento del calendario.
        Schema::create('activity_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'event_id']);
        });

        // Vínculo actividad <-> objetivo del plan de rama.
        Schema::create('activity_objective', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_plan_objective_id')->constrained()->cascadeOnDelete();
            $table->primary(['activity_id', 'branch_plan_objective_id'], 'activity_objective_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_objective');
        Schema::dropIfExists('activity_event');
    }
};
