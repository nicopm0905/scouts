<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Inscripciones a un evento (salidas/acampadas/campamentos). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->boolean('enrolled')->default(false);
            // Enlace público tokenizado para que la familia confirme y suba la autorización.
            $table->string('public_token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('authorization_file_id')->nullable(); // autorización firmada en Drive
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_member');
    }
};
