<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Timeline de historia del grupo (página pública). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('photo_file_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_entries');
    }
};
