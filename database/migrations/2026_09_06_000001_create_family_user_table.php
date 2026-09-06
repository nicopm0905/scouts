<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo entre una cuenta (rol "familia") y uno o más núcleos familiares.
 * N:M a propósito: cubre custodia compartida (una cuenta ligada a dos familias)
 * y varias cuentas por familia (padre y madre con acceso propio).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['family_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_user');
    }
};
