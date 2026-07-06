<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Checklist de documentación legal de un evento (autorización Junta, seguro, listados...). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('label'); // "Autorización Junta de Andalucía", "Seguro", "Listado asistentes"...
            $table->boolean('done')->default(false);
            $table->string('drive_file_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checklist_items');
    }
};
