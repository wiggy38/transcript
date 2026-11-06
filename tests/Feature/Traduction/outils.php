<?php

// Outils partagés des tests de la traduction (V2). Chargés par require_once depuis chaque fichier de test.
// Le faux modèle et les utilisateurs viennent du résumé.

require_once __DIR__.'/../Resume/outils.php';

use App\Data\Ia\ReponseModele;
use App\Enums\UniteConsommation;
use App\Jobs\GenererTraduction;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\TranscriptionWord;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Traduction\LancerTraduction;
use App\Services\Traduction\ServiceTraduction;

/**
 * Transcription terminée avec des mots régulièrement espacés : le découpage de F4 en fait des segments.
 * 500 mots de 600 ms donnent une vidéo de 5 minutes, soit une soixantaine de segments (deux morceaux).
 */
function traductionTranscription(User $utilisateur, int $mots = 500, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(array_merge([
        'duree_secondes' => (int) ceil($mots * 0.6),
        'langue_detectee' => 'fr',
        'titre' => 'Mon balcon',
    ], $attributs));

    for ($i = 0; $i < $mots; $i++) {
        TranscriptionWord::create([
            'transcription_id' => $transcription->getKey(),
            'position' => $i + 1,
            'texte' => ($i % 9 === 8) ? 'jardin.' : 'balcon',
            'debut_ms' => $i * 600,
            'fin_ms' => $i * 600 + 500,
        ]);
    }

    return $transcription;
}

/** @return array<string, AiOutput> */
function traductionLancer(User $utilisateur, Transcription $transcription, array $langues): array
{
    return app(LancerTraduction::class)->lancer($utilisateur, $transcription, $langues);
}

/** Exécute la tâche comme la file le fait. */
function traductionExecuter(AiOutput $sortie): void
{
    (new GenererTraduction($sortie->getKey()))->handle(app(ServiceTraduction::class));
}

/**
 * Structure valide pour un morceau : chaque identifiant reçoit le préfixe suivi du texte d'origine.
 *
 * @param  list<array<string, mixed>>  $segments
 */
function traductionMorceau(array $segments, string $prefixe = 'EN : '): array
{
    return [
        'segments' => array_map(fn (array $s) => ['id' => $s['id'], 'texte' => $prefixe.$s['texte_original']], $segments),
    ];
}

/** Segments d'une sortie lancée, dans l'ordre. */
function traductionSegments(AiOutput $sortie): array
{
    return $sortie->contenu['segments'];
}

/** Morceau de segments commençant à l'index donné (30 segments par morceau, comme la configuration par défaut). */
function traductionMorceauN(array $segments, int $debut, int $taille = 30): array
{
    return array_slice($segments, $debut, $taille);
}

function traductionCreditsConsommes(User $utilisateur): int
{
    return (int) UsageRecord::where('user_id', $utilisateur->getKey())
        ->where('unite', UniteConsommation::CreditIa)
        ->where('motif', 'traduction')
        ->sum('quantite');
}

function traductionReponseModele(array $structure, float $cout = 0.001): ReponseModele
{
    return resumeReponse($structure, $cout);
}
