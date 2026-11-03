<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paragraphes calculés à partir des mots : début, fin et texte joint.
     *
     * L'index plein texte n'existe que sur PostgreSQL. Il porte sur
     * to_tsvector('simple', texte) : pas de colonne supplémentaire, donc aucun
     * tsvector ramené à PHP à chaque lecture de paragraphe.
     */
    public function up(): void
    {
        Schema::create('transcription_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedInteger('debut_ms');
            $table->unsignedInteger('fin_ms');
            $table->text('texte');

            $table->unique(['transcription_id', 'position']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX transcription_paragraphs_recherche_index '
                ."ON transcription_paragraphs USING GIN (to_tsvector('simple', texte))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transcription_paragraphs');
    }
};
