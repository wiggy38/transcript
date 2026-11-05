<?php

use App\Support\ConfigurationRecherche;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Texte de recherche d'un résumé terminé : résumé, points clés et chapitres, sans la structure JSON.
     * Rempli à la réussite seulement. Sur PostgreSQL, un index GIN partiel par configuration, comme
     * pour les paragraphes ; la requête de l'historique reprend les mêmes conditions.
     */
    public function up(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->text('texte_recherche')->nullable()->after('contenu');
            $table->string('config_recherche', 20)->default('simple')->after('texte_recherche');
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (ConfigurationRecherche::toutes() as $configuration) {
                DB::statement(
                    "CREATE INDEX ai_outputs_recherche_{$configuration} ON ai_outputs USING GIN (to_tsvector('{$configuration}', texte_recherche)) "
                    ."WHERE config_recherche = '{$configuration}' AND statut = 'termine'"
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropColumn(['texte_recherche', 'config_recherche']);
        });
    }
};
