<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Formaciones de un responsable: manipulador de alimentos, primeros auxilios, MSC Módulo 0... */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leader_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // texto libre: "Manipulador de alimentos", "Primeros auxilios", "MSC Módulo 0"
            $table->date('obtained_at')->nullable();
            $table->date('expires_at')->nullable(); // caducidad opcional
            $table->string('drive_file_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leader_trainings');
    }
};
