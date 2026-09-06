<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_member', function (Blueprint $table) {
            $table->longText('signature_data')->nullable()->after('authorization_file_id');
            $table->boolean('medical_consent')->default(true)->after('signature_data');
            $table->boolean('image_consent')->default(true)->after('medical_consent');
        });
    }

    public function down(): void
    {
        Schema::table('event_member', function (Blueprint $table) {
            $table->dropColumn(['signature_data', 'medical_consent', 'image_consent']);
        });
    }
};
