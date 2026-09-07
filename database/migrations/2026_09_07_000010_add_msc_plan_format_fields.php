<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos que faltaban para que el plan de rama y las actividades se rellenen y
 * se impriman con el formato oficial MSC (hoja de programación trimestral de la
 * delegación y ficha de salida).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_plan_objectives', function (Blueprint $table) {
            // Ámbito (Responsabilidad / País / Fe) y línea dentro del ámbito.
            $table->string('scope')->nullable()->after('branch_plan_id');
            $table->string('line')->nullable()->after('scope');
            // "¿Cómo estamos?": el punto de partida de la unidad.
            $table->text('current_situation')->nullable()->after('content');
            // "¿Qué queremos conseguir?": verbo + complemento, en dos columnas.
            $table->string('goal_verb')->nullable()->after('current_situation');
            $table->text('goal_complement')->nullable()->after('goal_verb');
            // "¿Cómo ha salido?": la evaluación al cerrar el trimestre.
            $table->text('evaluation')->nullable()->after('status');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('activity_type')->nullable()->after('branch');
            $table->string('owner')->nullable()->after('activity_type');   // Encargado
            $table->date('scheduled_date')->nullable()->after('owner');    // Fecha
            $table->string('place')->nullable()->after('scheduled_date');  // Sitio
            $table->text('evaluation')->nullable()->after('materials_text');
        });
    }

    public function down(): void
    {
        Schema::table('branch_plan_objectives', function (Blueprint $table) {
            $table->dropColumn(['scope', 'line', 'current_situation', 'goal_verb', 'goal_complement', 'evaluation']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['activity_type', 'owner', 'scheduled_date', 'place', 'evaluation']);
        });
    }
};
