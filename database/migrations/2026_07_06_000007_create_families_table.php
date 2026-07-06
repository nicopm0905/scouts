<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Una familia agrupa hermanos scouts y sus tutores de contacto. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Familia García López"
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Pivote miembro <-> familia con parentesco.
        Schema::create('family_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('relationship'); // App\Enums\FamilyRelationship
            $table->timestamps();

            $table->unique(['family_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_member');
        Schema::dropIfExists('families');
    }
};
