<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reparto de tareas del kraal para preparar una salida: cada punto de la checklist
 * del evento puede asignarse a una persona con una fecha límite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_checklist_items', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('done')->constrained('users')->nullOnDelete();
            $table->date('due_at')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('event_checklist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('due_at');
        });
    }
};
