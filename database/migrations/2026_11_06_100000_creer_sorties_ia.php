<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sorties générées par le modèle de langage (V2) : une ligne par transcription et par fonctionnalité.
     * La régénération remplace le contenu sur la même ligne ; le résumé conservé se rouvre sans coût.
     *
     * usage_records.ai_output_id relie le décompte de crédits à la sortie qui l'a consommé.
     */
    public function up(): void
    {
        Schema::create('ai_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcription_id')->constrained()->cascadeOnDelete();
            $table->string('fonctionnalite', 40);
            $table->string('statut', 20);
            $table->string('langue', 8);
            // Contenu validé (résumé, points clés, chapitres) ; null tant qu'aucune version n'a réussi.
            $table->json('contenu')->nullable();
            // Crédits IA réservés au lancement (décomptés seulement à la réussite).
            $table->unsignedInteger('credits')->default(0);
            $table->string('fournisseur', 40)->nullable();
            $table->string('modele', 80)->nullable();
            $table->decimal('cout_estime_usd', 10, 4)->default(0);
            $table->timestamp('date_debut')->nullable();
            $table->timestamp('date_termine')->nullable();
            $table->timestamp('date_echec')->nullable();
            $table->timestamps();

            $table->unique(['transcription_id', 'fonctionnalite']);
            $table->index(['statut']);
        });

        Schema::table('usage_records', function (Blueprint $table) {
            $table->foreignId('ai_output_id')->nullable()->after('script_analysis_id')
                ->constrained('ai_outputs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_output_id');
        });

        Schema::dropIfExists('ai_outputs');
    }
};
