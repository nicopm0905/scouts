<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_member', function (Blueprint $table) {
            $table->string('family_name')->nullable()->after('image_consent');
            $table->string('family_dni')->nullable()->after('family_name');
            $table->string('contact_phone')->nullable()->after('family_dni');
            $table->text('health_summary')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('event_member', function (Blueprint $table) {
            $table->dropColumn(['family_name', 'family_dni', 'contact_phone', 'health_summary']);
        });
    }
};
