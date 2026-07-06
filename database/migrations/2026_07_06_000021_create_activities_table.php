<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Biblioteca reutilizable de actividades. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('branch')->nullable(); // MemberRole o null = "todas"
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('objectives_text')->nullable();
            $table->longText('development')->nullable(); // desarrollo paso a paso (markdown)
            $table->json('attachment_file_ids')->nullable(); // adjuntos en Drive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('branch');
        });

        // Materiales necesarios para la actividad; enlace opcional a ítem de inventario.
        Schema::create('activity_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_materials');
        Schema::dropIfExists('activities');
    }
};
