<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location_city')->nullable();
            $table->boolean('has_eucharist')->default(false);
            $table->boolean('has_hike')->default(false);
            $table->text('theme_description')->nullable();
        });

        Schema::table('branch_plan_objectives', function (Blueprint $table) {
            $table->string('development_area')->nullable()->after('branch_plan_id');
            $table->text('content')->nullable()->after('description');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('day_number')->nullable()->after('duration_minutes');
            $table->string('time_slot')->nullable()->after('day_number');
            $table->integer('activity_number')->nullable()->after('time_slot');
            $table->text('materials_text')->nullable()->after('development');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['coordinator_id']);
            $table->dropColumn(['coordinator_id', 'location_city', 'has_eucharist', 'has_hike', 'theme_description']);
        });

        Schema::table('branch_plan_objectives', function (Blueprint $table) {
            $table->dropColumn(['development_area', 'content']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['day_number', 'time_slot', 'activity_number', 'materials_text']);
        });
    }
};
