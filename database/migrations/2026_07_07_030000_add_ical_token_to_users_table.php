<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token personal para la suscripción iCal de solo lectura al calendario de eventos
 * (Agente C - Calendario y eventos). Se genera perezosamente la primera vez que el
 * usuario visita el calendario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ical_token', 64)->nullable()->unique()->after('branches');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ical_token');
        });
    }
};
