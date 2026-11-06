<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bibliothèque classée (V2, Pro et Business) : dossiers, étiquettes et leur liaison aux transcriptions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 80);
            // Minuscules et espaces réduits : sert à l'unicité par compte, sans tenir compte de la casse.
            $table->string('nom_normalise', 80);
            $table->timestamps();

            $table->unique(['user_id', 'nom_normalise']);
        });

        Schema::create('etiquettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // niche, produit, concurrent, libre (enum TypeEtiquette).
            $table->string('type', 20);
            $table->string('nom', 80);
            $table->string('nom_normalise', 80);
            $table->timestamps();

            // Un même nom peut exister dans deux types différents, jamais deux fois dans le même type.
            $table->unique(['user_id', 'type', 'nom_normalise']);
            // Panneau latéral et autocomplétion : étiquettes d'un compte, par type.
            $table->index(['user_id', 'type']);
        });

        Schema::create('etiquette_transcription', function (Blueprint $table) {
            $table->foreignId('transcription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('etiquette_id')->constrained('etiquettes')->cascadeOnDelete();

            $table->primary(['transcription_id', 'etiquette_id']);
            // Filtre par étiquette : les transcriptions d'une étiquette.
            $table->index('etiquette_id');
        });

        Schema::table('transcriptions', function (Blueprint $table) {
            // Une transcription est dans un dossier au plus. Supprimer le dossier la remet en « Non classé ».
            $table->foreignId('dossier_id')->nullable()->after('user_id')->constrained('dossiers')->nullOnDelete();

            // Liste d'un dossier, triée par date (vue « Non classé » et dossiers).
            $table->index(['user_id', 'dossier_id', 'created_at']);
            // Filtre par plateforme, trié par date.
            $table->index(['user_id', 'source', 'created_at']);
            // Regroupement et filtre par chaîne ou compte d'origine (colonne chaine).
            $table->index(['user_id', 'chaine']);
        });
    }

    public function down(): void
    {
        Schema::table('transcriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'dossier_id', 'created_at']);
            $table->dropIndex(['user_id', 'source', 'created_at']);
            $table->dropIndex(['user_id', 'chaine']);
            $table->dropConstrainedForeignId('dossier_id');
        });

        Schema::dropIfExists('etiquette_transcription');
        Schema::dropIfExists('etiquettes');
        Schema::dropIfExists('dossiers');
    }
};
