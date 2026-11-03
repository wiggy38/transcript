<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurations de recherche PostgreSQL utilisées (voir config/voxia.php, « recherche »).
     * Une par langue, plus « simple » pour les langues non listées.
     */
    private const CONFIGURATIONS = ['simple', 'french', 'english', 'spanish', 'german', 'italian', 'portuguese'];

    /**
     * Recherche par langue : chaque paragraphe garde la configuration de sa transcription, et
     * un index GIN partiel existe par configuration. Une requête qui fixe la configuration
     * dans son WHERE n'utilise ainsi que l'index de sa langue.
     *
     * Rappel : date d'envoi de l'email « suppression prochaine » (plan Gratuit, cahier F5).
     */
    public function up(): void
    {
        Schema::table('transcription_paragraphs', function (Blueprint $table) {
            $table->string('config_recherche', 20)->default('simple')->after('texte');
        });

        Schema::table('transcriptions', function (Blueprint $table) {
            $table->timestamp('date_rappel_suppression')->nullable()->after('date_echec');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // L'index unique sur to_tsvector('simple') de la migration précédente est remplacé.
        DB::statement('DROP INDEX IF EXISTS transcription_paragraphs_recherche_index');

        foreach (self::CONFIGURATIONS as $configuration) {
            DB::statement(
                "CREATE INDEX transcription_paragraphs_recherche_{$configuration} "
                ."ON transcription_paragraphs USING GIN (to_tsvector('{$configuration}', texte)) "
                ."WHERE config_recherche = '{$configuration}'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (self::CONFIGURATIONS as $configuration) {
                DB::statement("DROP INDEX IF EXISTS transcription_paragraphs_recherche_{$configuration}");
            }

            DB::statement(
                'CREATE INDEX transcription_paragraphs_recherche_index '
                ."ON transcription_paragraphs USING GIN (to_tsvector('simple', texte))"
            );
        }

        Schema::table('transcriptions', function (Blueprint $table) {
            $table->dropColumn('date_rappel_suppression');
        });

        Schema::table('transcription_paragraphs', function (Blueprint $table) {
            $table->dropColumn('config_recherche');
        });
    }
};
