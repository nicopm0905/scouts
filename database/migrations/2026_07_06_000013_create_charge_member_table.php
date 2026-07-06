<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Reparto de un cobro a cada miembro con su estado de pago. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            // Importe efectivo para este miembro (tras aplicar descuento por hermanos).
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // App\Enums\ChargeStatus
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // App\Enums\PaymentMethod
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['charge_id', 'member_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_member');
    }
};
