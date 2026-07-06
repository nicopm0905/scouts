<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos extra en users. El rol funcional se gestiona con spatie/permission,
 * pero cacheamos las ramas asignadas al responsable para el scope de visibilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ramas que puede ver/gestionar un usuario responsable (array de MemberRole).
            $table->json('branches')->nullable()->after('email');
            $table->boolean('active')->default(true)->after('branches');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branches', 'active']);
        });
    }
};
