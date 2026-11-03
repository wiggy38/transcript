<?php

use App\Enums\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// Test de charge (cahier F5) : 500 transcriptions factices pour un compte, recherche < 1 seconde.
// PostgreSQL uniquement : la recherche plein texte n'existe pas sur SQLite.
uses(RefreshDatabase::class);

const NOMBRE_TRANSCRIPTIONS_CHARGE = 500;
const PARAGRAPHES_PAR_TRANSCRIPTION = 40;

/** Vocabulaire des paragraphes factices. « lancement » revient dans un paragraphe sur vingt. */
function vocabulaireCharge(): array
{
    return [
        'vidéo', 'produit', 'marque', 'client', 'script', 'publicité', 'texte', 'horodatage',
        'atelier', 'poterie', 'café', 'voyage', 'cuisine', 'musique', 'sport', 'santé', 'finance',
        'maison', 'jardin', 'livre', 'film', 'conseil', 'méthode', 'résultat', 'stratégie', 'équipe',
        'budget', 'semaine', 'question', 'réponse', 'exemple', 'histoire', 'objectif', 'avantage',
    ];
}

function remplirCharge(User $utilisateur): void
{
    $mots = vocabulaireCharge();
    $maintenant = now();
    $paragraphes = [];

    for ($i = 1; $i <= NOMBRE_TRANSCRIPTIONS_CHARGE; $i++) {
        $identifiant = DB::table('transcriptions')->insertGetId([
            'user_id' => $utilisateur->getKey(),
            'source' => 'youtube',
            'url_origine' => "https://www.youtube.com/watch?v=charge{$i}",
            'id_video' => 'charge'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            'titre' => "Transcription factice {$i}",
            'duree_secondes' => 600,
            'langue_detectee' => 'fr',
            'statut' => 'termine',
            'nombre_mots' => PARAGRAPHES_PAR_TRANSCRIPTION * 25,
            'cout_estime_usd' => 0.0367,
            'est_apercu' => false,
            'created_at' => $maintenant->copy()->subMinutes($i),
            'updated_at' => $maintenant,
        ]);

        for ($p = 1; $p <= PARAGRAPHES_PAR_TRANSCRIPTION; $p++) {
            $texte = [];

            for ($m = 0; $m < 25; $m++) {
                $texte[] = $mots[($i * 31 + $p * 17 + $m * 7) % count($mots)];
            }

            if ($p % 20 === 0) {
                $texte[] = 'lancement';
            }

            $paragraphes[] = [
                'transcription_id' => $identifiant,
                'position' => $p,
                'debut_ms' => ($p - 1) * 15_000,
                'fin_ms' => $p * 15_000,
                'texte' => implode(' ', $texte),
                'config_recherche' => 'french',
            ];
        }
    }

    foreach (array_chunk($paragraphes, 1000) as $bloc) {
        DB::table('transcription_paragraphs')->insert($bloc);
    }
}

it('répond en moins d’une seconde pour une recherche sur 500 transcriptions', function () {
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    app()->setLocale('fr');

    remplirCharge($utilisateur);

    expect(DB::table('transcriptions')->where('user_id', $utilisateur->getKey())->count())->toBe(NOMBRE_TRANSCRIPTIONS_CHARGE);

    $mesures = [];

    // Trois recherches : la première est à froid, les suivantes à chaud.
    foreach (range(1, 3) as $essai) {
        $debut = hrtime(true);

        $this->actingAs($utilisateur)
            ->get(route('historique', ['q' => 'lancement']))
            ->assertOk()
            ->assertSee('<mark', false);

        $mesures[] = (hrtime(true) - $debut) / 1_000_000;
    }

    fwrite(STDERR, sprintf(
        "\n[charge F5] %d transcriptions x %d paragraphes, recherche « lancement » (page complète) : %s ms\n",
        NOMBRE_TRANSCRIPTIONS_CHARGE,
        PARAGRAPHES_PAR_TRANSCRIPTION,
        implode(' / ', array_map(fn ($ms) => number_format($ms, 0, ',', ' '), $mesures)),
    ));

    foreach ($mesures as $ms) {
        expect($ms)->toBeLessThan(1000);
    }
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Recherche plein texte : PostgreSQL uniquement.');
