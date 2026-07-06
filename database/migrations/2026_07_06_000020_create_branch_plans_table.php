<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plan de rama por curso escolar (ej. "2026-2027"). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_plans', function (Blueprint $table) {
            $table->id();
            $table->string('branch'); // App\Enums\MemberRole (rama educativa)
            $table->string('school_year'); // "2026-2027"
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['branch', 'school_year']);
        });

        Schema::create('branch_plan_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_plan_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->unsignedTinyInteger('term')->nullable(); // trimestre 1..3
            $table->string('status')->default('pendiente'); // App\Enums\ObjectiveStatus
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_plan_objectives');
        Schema::dropIfExists('branch_plans');
    }
};
