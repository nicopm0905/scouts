<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de revisión de datos que envían las familias desde el portal.
 * No escriben directamente en `members`/`health_records`/`families`: secretaría
 * revisa y aplica (trazabilidad RGPD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // App\Enums\ChangeRequestStatus
            $table->json('payload'); // { member: {...}, health: {...} } solo los valores nuevos propuestos
            $table->text('note')->nullable();             // mensaje opcional de la familia
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();       // motivo del rechazo / nota de secretaría
            $table->timestamps();

            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_change_requests');
    }
};
