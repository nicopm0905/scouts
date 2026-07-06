<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Perfil de responsable 1:1 (solo miembros con role = responsable). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leader_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('qualification')->default('none'); // App\Enums\LeaderQualification
            $table->date('sexual_offenses_certificate_date')->nullable(); // certificado de delitos sexuales
            $table->date('sexual_offenses_certificate_expires_at')->nullable();
            // Ramas de las que es responsable (para el scope de visibilidad). Array de MemberRole::branches.
            $table->json('branches')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leader_profiles');
    }
};
