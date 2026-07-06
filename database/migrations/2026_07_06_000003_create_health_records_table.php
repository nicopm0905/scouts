<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ficha sanitaria 1:1 con el miembro. Datos sensibles. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('allergies')->nullable();
            $table->text('intolerances')->nullable();
            $table->text('medication')->nullable();
            $table->text('observations')->nullable();
            $table->string('health_card_number')->nullable();
            $table->string('drive_file_id')->nullable(); // adjunto en Drive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
