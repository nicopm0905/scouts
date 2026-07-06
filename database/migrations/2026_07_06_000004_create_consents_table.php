<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Consentimientos del miembro: RGPD, imagen, salidas periódicas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // App\Enums\ConsentType
            $table->boolean('granted')->default(false);
            $table->date('signed_at')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
