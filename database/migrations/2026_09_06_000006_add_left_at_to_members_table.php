<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha en que un miembro deja de estar activo (baja), para poder medir altas y
 * bajas por curso en la memoria. Se mantiene sola: el modelo Member la rellena
 * cuando `active` pasa a false y la limpia cuando vuelve a true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->date('left_at')->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('left_at');
        });
    }
};
