<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Documentos de secretaría por categoría (censo, seguros, actas, estatutos...). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category'); // App\Enums\DocumentCategory
            $table->string('drive_file_id')->nullable();
            $table->string('external_url')->nullable();
            $table->date('expires_at')->nullable(); // documentos con caducidad -> alerta en dashboard
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
