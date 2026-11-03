<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Source et identité de la vidéo. id_video est nul pour un fichier importé :
            // l'index unique ne bloque alors pas les fichiers multiples.
            $table->string('source', 20);
            $table->text('url_origine')->nullable();
            $table->string('id_video', 64)->nullable();

            // Métadonnées récupérées avant le lancement (F1).
            $table->string('titre', 500)->nullable();
            $table->string('chaine', 255)->nullable();
            $table->text('miniature_url')->nullable();
            $table->unsignedInteger('duree_secondes')->nullable();

            // Langue forcée par l'utilisateur, langue détectée par le moteur.
            $table->string('langue_forcee', 8)->nullable();
            $table->string('langue_detectee', 8)->nullable();

            // Statut et motif d'échec (F1, F5).
            $table->string('statut', 20)->default('en_attente');
            $table->string('motif_echec', 30)->nullable();

            // Aperçu gratuit limité à 10 minutes (cahier §5).
            $table->boolean('est_apercu')->default(false);

            // MP3 (F3).
            $table->string('mp3_chemin')->nullable();
            $table->unsignedInteger('mp3_duree_secondes')->nullable();

            // Volume et coût estimé.
            $table->unsignedInteger('nombre_mots')->default(0);
            $table->decimal('cout_estime_usd', 10, 4)->default(0);

            // Date de chaque étape ; created_at tient lieu de date de demande.
            $table->timestamp('date_metadonnees')->nullable();
            $table->timestamp('date_telechargement')->nullable();
            $table->timestamp('date_extraction_audio')->nullable();
            $table->timestamp('date_transcription')->nullable();
            $table->timestamp('date_termine')->nullable();
            $table->timestamp('date_echec')->nullable();

            $table->timestamps();

            // Un même lien déjà transcrit par le même utilisateur renvoie vers l'existant (F1).
            $table->unique(['user_id', 'source', 'id_video']);
            // Historique trié par date (F5).
            $table->index(['user_id', 'created_at']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcriptions');
    }
};
