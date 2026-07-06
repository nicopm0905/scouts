<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Facturas recibidas (gasto) y emitidas. Las emitidas llevan serie autonumérica. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // App\Enums\InvoiceDirection
            $table->string('number')->nullable(); // serie autonumérica solo para emitidas
            $table->date('date');
            $table->string('supplier_or_client');
            $table->string('concept');
            $table->decimal('amount', 10, 2);
            $table->decimal('vat', 10, 2)->default(0);
            $table->string('category'); // App\Enums\InvoiceCategory
            $table->string('drive_file_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('date');
            $table->index('direction');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
