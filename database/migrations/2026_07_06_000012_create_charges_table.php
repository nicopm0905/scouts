<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cobro (cuota, salida, campamento...). Se reparte a miembros vía charge_member. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('type'); // App\Enums\ChargeType
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            // Ramas destinatarias seleccionadas al crear el cobro (para trazabilidad).
            $table->json('target_branches')->nullable();
            $table->boolean('sibling_discount_applies')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('due_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
