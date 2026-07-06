<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actas de consejo/asamblea. Entidad estructurada que genera PDF y se sube a Drive.
 * Asistentes se guardan como pivote minute_member.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('actas_consejo'); // reutiliza DocumentCategory (actas_consejo|actas_asamblea)
            $table->date('held_on'); // fecha de la reunión
            $table->string('location')->nullable();
            $table->string('drive_file_id')->nullable(); // PDF generado subido a Drive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('held_on');
        });

        // Asistentes al acta (miembros responsables).
        Schema::create('minute_member', function (Blueprint $table) {
            $table->foreignId('minute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->primary(['minute_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minute_member');
        Schema::dropIfExists('minutes');
    }
};
