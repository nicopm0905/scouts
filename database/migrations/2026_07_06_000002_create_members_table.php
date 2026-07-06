<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable(); // de contacto; en menores el de la familia
            $table->string('role'); // App\Enums\MemberRole
            $table->date('birth_date')->nullable(); // necesaria para ratios legales por edad
            $table->boolean('active')->default(true);
            $table->date('joined_at')->nullable();
            // Enlace opcional a la cuenta de usuario (un responsable puede tener login)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
