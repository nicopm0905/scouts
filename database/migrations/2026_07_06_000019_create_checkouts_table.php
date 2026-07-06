<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Reservas/préstamos de material: para un evento o sacado por un responsable. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            // Destinatario: un evento y/o un responsable.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->date('checked_out_at');
            $table->date('expected_return_at')->nullable();
            $table->date('returned_at')->nullable(); // null = aún fuera
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('expected_return_at');
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
