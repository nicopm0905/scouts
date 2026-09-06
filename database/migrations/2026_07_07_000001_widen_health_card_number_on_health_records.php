<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `health_card_number` pasa a cifrarse en reposo (cast `encrypted`): el payload
 * cifrado (~250-400 chars) no cabe con garantías en un varchar(255) → TEXT.
 * El resto de campos médicos ya eran TEXT en la migración original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->text('health_card_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->string('health_card_number')->nullable()->change();
        });
    }
};
