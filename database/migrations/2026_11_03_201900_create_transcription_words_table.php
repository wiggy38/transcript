<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un mot par ligne, avec ses bornes en millisecondes (sous-titres F4, lecteur F2).
     * Les lignes sont écrites en lot par le worker, sans horodatage Eloquent.
     */
    public function up(): void
    {
        Schema::create('transcription_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('texte', 255);
            $table->unsignedInteger('debut_ms');
            $table->unsignedInteger('fin_ms');

            $table->unique(['transcription_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcription_words');
    }
};
