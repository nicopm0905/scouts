<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Firma digital simple de Consent/Document por email, con enlace público por token. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable'); // signable_type, signable_id (Consent o Document)
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('public_token', 64)->unique();
            $table->string('status', 20)->default('pending'); // App\Enums\SignatureStatus
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Rastro de auditoría de la firma.
            $table->string('signer_name')->nullable();
            $table->longText('signature_image')->nullable(); // dataURL PNG del trazo dibujado
            $table->string('signer_ip', 45)->nullable();
            $table->text('signer_user_agent')->nullable();
            $table->string('source_document_hash', 64)->nullable();
            $table->string('signed_drive_file_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['signable_type', 'signable_id', 'member_id']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
