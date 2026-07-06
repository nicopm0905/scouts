<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // App\Enums\InventoryCategory
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition')->default('bueno'); // App\Enums\ItemCondition
            $table->string('location')->nullable(); // "local", "contenedor", "casa de..."
            $table->date('next_review_at')->nullable(); // revisión (botiquín, etc.) -> alerta dashboard
            $table->string('photo_file_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('condition');
            $table->index('next_review_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
