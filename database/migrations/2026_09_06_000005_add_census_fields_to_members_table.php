<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos necesarios para el censo oficial (federación MSC): documento de
 * identidad, sexo y dirección postal. Opcionales en la ficha del miembro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->after('email');
            $table->string('sex', 1)->nullable()->after('dni'); // 'M' | 'F' | 'X'
            $table->string('address')->nullable()->after('sex');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['dni', 'sex', 'address']);
        });
    }
};
