<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Puntos del orden del día de un acta, cada uno con su acuerdo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minute_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minute_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('topic'); // punto del orden del día
            $table->text('discussion')->nullable(); // desarrollo
            $table->text('agreement')->nullable(); // acuerdo adoptado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minute_items');
    }
};
