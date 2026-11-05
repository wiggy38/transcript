<?php

require_once __DIR__.'/outils.php';

use App\Data\Resume\ParagrapheResume;
use App\Enums\MotifErreurModele;
use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Enums\UniteConsommation;
use App\Exceptions\ErreurModele;
use App\Exceptions\ResumeRefuse;
use App\Jobs\GenererResume;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Quota\QuotaService;
use App\Services\Resume\DecoupeurTranscription;
use App\Services\Resume\LancerResume;
use App\Services\Resume\ServiceResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

/** Lance le résumé comme le fait la file : la tâche appelle le service avec la sortie. */
function resumeGenerer(AiOutput $sortie): void
{
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));
}

function resumeLancer(User $utilisateur, Transcription $transcription, ?string $langue = null, bool $regenerer = false, bool $confirme = false): AiOutput
{
    return app(LancerResume::class)->lancer($utilisateur, $transcription, $langue, $regenerer, $confirme);
}

function resumeCreditsConsommes(User $utilisateur): int
{
    return (int) UsageRecord::where('user_id', $utilisateur->getKey())
        ->where('unite', UniteConsommation::CreditIa)
        ->sum('quantite');
}

/** Paragraphes de la fixture de 28 minutes, numérotés comme le service les numérote. @return list<ParagrapheResume> */
function resumeParagraphesFixtureNumerotes(): array
{
    return array_map(
        fn (array $p, int $index) => new ParagrapheResume($index + 1, $p[0], $p[1]),
        resumeParagraphesFixture(),
        array_keys(resumeParagraphesFixture()),
    );
}

/** Sortie d'un morceau de la fixture (paragraphes de début à début + 1), sans chiffre. */
function resumeSortieMorceau(int $premierParagraphe): array
{
    return [
        'resume' => 'Le balcon reçoit peu de soleil au nord. Les bacs doivent être légers. Le drainage évite les racines dans l’eau. Les aromatiques supportent la sécheresse.',
        'points_cles' => [
            ['texte' => 'Choisir un balcon bien orienté.', 'paragraphe' => $premierParagraphe],
            ['texte' => 'Préférer des bacs légers.', 'paragraphe' => $premierParagraphe + 1],
        ],
        'chapitres' => [
            ['titre' => 'Le choix du balcon', 'paragraphe' => $premierParagraphe, 'resume' => 'L’orientation du balcon compte plus que le matériel. Elle décide des plantes possibles.'],
        ],
    ];
}

beforeEach(function () {
    // Un seul appel et une seule relance par défaut, comme en production (voxia.ia.resume.relances = 1).
    config(['voxia.ia.resume.relances' => 1]);
});

// Critère : une vidéo courte est résumée en un appel, et un crédit est décompté à la réussite.
it('résume une vidéo courte en un appel et décompte un crédit', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($sortie->contenu['points'])->toHaveCount(5)
        ->and($sortie->contenu['points'][0]['debut_ms'])->toBe(20000)
        ->and(resumeFaux()->requetes)->toHaveCount(1)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(1);
});

// Critère fidélité : un horodatage inventé est retiré, la sortie reste valable tant qu'il reste assez de points.
it('retire un horodatage inventé sans relancer tant que le minimum est atteint', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $structure = resumeSortieValide();
    $structure['points_cles'][] = ['texte' => 'Point inventé.', 'paragraphe' => 999];
    resumeFaux()->repondre(resumeReponse($structure));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeFaux()->requetes)->toHaveCount(1)
        ->and(collect($sortie->fresh()->contenu['points'])->pluck('texte')->all())->not->toContain('Point inventé.');
});

// Critère fidélité : des horodatages inventés qui font passer sous le minimum déclenchent une relance.
it('relance une fois quand les horodatages inventés font passer sous le minimum', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);

    $invalide = resumeSortieValide();
    $invalide['points_cles'] = array_slice($invalide['points_cles'], 0, 3);
    $invalide['points_cles'][] = ['texte' => 'Inventé.', 'paragraphe' => 999];
    $invalide['points_cles'][] = ['texte' => 'Inventé aussi.', 'paragraphe' => 998];

    resumeFaux()->repondre(resumeReponse($invalide))->repondre(resumeReponse(resumeSortieValide()));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeFaux()->requetes)->toHaveCount(2)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(1);
});

// Critère : une réponse invalide est relancée une fois ; si elle échoue encore, aucun crédit n'est consommé.
it('échoue sans décompter de crédit après une réponse invalide relancée', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $deux = resumeSortieValide();
    $deux['resume'] = 'Une phrase. Deux phrases.';

    resumeFaux()->repondre(resumeReponse($deux))->repondre(resumeReponse($deux));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(resumeFaux()->requetes)->toHaveCount(2)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(0);
});

// Critère : un échec technique du fournisseur n'entraîne aucun décompte.
it('échoue sans décompter de crédit après une erreur technique du modèle', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(new ErreurModele(MotifErreurModele::Connexion, 0.0004));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and((float) $sortie->fresh()->cout_estime_usd)->toBe(0.0004)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(0);
});

// Critère : un délai dépassé de la tâche finit en échec, jamais en attente, sans décompte.
it('passe en échec quand la tâche dépasse son délai', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $sortie = resumeLancer($utilisateur, resumeTranscriptionCourte($utilisateur));

    (new GenererResume($sortie->getKey()))->failed(new RuntimeException('timeout'));

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(0);
});

// Critère crédits : solde insuffisant, refus avant tout appel au modèle.
it('refuse le lancement quand le solde de crédits IA est insuffisant', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    app(QuotaService::class)->consommerCredits($utilisateur, 30, 'test', $transcription);

    expect(fn () => resumeLancer($utilisateur, $transcription))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::CREDIT_INSUFFISANT)
        ->and(resumeFaux()->requetes)->toHaveCount(0);
});

// Critère crédits : un résumé en cours réserve ses crédits, un second ne peut pas dépasser le solde.
it('compte les crédits réservés par un résumé en cours', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $premiere = resumeTranscriptionCourte($utilisateur);
    $seconde = resumeTranscriptionCourte($utilisateur);
    app(QuotaService::class)->consommerCredits($utilisateur, 29, 'test', $premiere);

    resumeLancer($utilisateur, $premiere);

    expect(fn () => resumeLancer($utilisateur, $seconde))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::CREDIT_INSUFFISANT);
});

// Critère plan : Gratuit et Pack n'ont pas de crédits IA, donc pas d'accès au résumé.
it('refuse le résumé aux plans sans crédits IA', function (Plan $plan) {
    $utilisateur = resumeUtilisateur($plan);
    $transcription = resumeTranscriptionCourte($utilisateur);

    expect(fn () => resumeLancer($utilisateur, $transcription))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::PLAN_SANS_ACCES)
        ->and(resumeFaux()->requetes)->toHaveCount(0);
})->with([Plan::Free, Plan::Pack]);

// Critère : un compte ne résume que ses propres transcriptions.
it('refuse le résumé d’une transcription d’un autre compte', function () {
    $proprietaire = resumeUtilisateur(Plan::Pro);
    $autre = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($proprietaire);

    expect(fn () => resumeLancer($autre, $transcription))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::NON_TERMINEE);
});

// Critère : rouvrir un résumé terminé est gratuit.
it('rouvre un résumé terminé sans nouvel appel ni décompte', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));
    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    $rouverte = resumeLancer($utilisateur, $transcription);

    expect($rouverte->getKey())->toBe($sortie->getKey())
        ->and($rouverte->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeFaux()->requetes)->toHaveCount(1)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(1);
});

// Critère : régénérer demande une confirmation, puis coûte de nouveaux crédits.
it('exige une confirmation pour régénérer, puis décompte de nouveau', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()))->repondre(resumeReponse(resumeSortieValide()));
    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect(fn () => resumeLancer($utilisateur, $transcription, null, regenerer: true))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::CONFIRMATION_REQUISE);

    $regeneree = resumeLancer($utilisateur, $transcription, null, regenerer: true, confirme: true);
    resumeGenerer($regeneree);

    expect($regeneree->fresh()->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(2);
});

// Critère coût : un crédit par heure entamée.
it('compte un crédit par heure entamée', function (int $secondes, int $attendu) {
    $transcription = (new Transcription)->forceFill(['duree_secondes' => $secondes]);

    expect(app(LancerResume::class)->coutPour($transcription))->toBe($attendu);
})->with([
    'vingt-huit minutes' => [1680, 1],
    'une heure pile' => [3600, 1],
    'une heure une seconde' => [3601, 2],
    'deux heures et demie' => [9000, 3],
]);

// Critère : la langue par défaut est celle de la transcription, et une langue hors liste est refusée.
it('propose la langue de la transcription et refuse une langue hors liste', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur, ['langue_detectee' => 'en']);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));

    expect(app(LancerResume::class)->langueParDefaut($transcription))->toBe('en')
        ->and(fn () => resumeLancer($utilisateur, $transcription, 'ja'))
        ->toThrow(ResumeRefuse::class, ResumeRefuse::LANGUE_INVALIDE);

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect(resumeFaux()->requetes[0]->instructions)->toContain('en anglais');
});

// Critère sécurité : la transcription part dans les données, jamais dans les instructions.
it('envoie la transcription comme donnée, jamais dans la consigne', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));

    resumeGenerer(resumeLancer($utilisateur, $transcription));

    $requete = resumeFaux()->requetes[0];

    expect($requete->instructions)->not->toContain('Bonjour, je vous présente')
        ->and($requete->donnees[0])->toContain('Paragraphe 1')
        ->and($requete->donnees[0])->toContain('Bonjour, je vous présente');
});

// Critère vidéos longues : le découpage ne coupe jamais un paragraphe.
it('découpe la fixture de 28 minutes en morceaux sans couper de paragraphe', function () {
    $morceaux = (new DecoupeurTranscription)->decouper(resumeParagraphesFixtureNumerotes(), 600_000);

    $numeros = array_merge(...array_map(fn (array $m) => array_map(fn ($p) => $p->numero, $m), $morceaux));

    expect($morceaux)->toHaveCount(3)
        ->and(array_map('count', $morceaux))->toBe([30, 30, 24])
        ->and($numeros)->toBe(range(1, 84))
        ->and($morceaux[1][0]->debutMs)->toBe(600_000)
        ->and($morceaux[2][0]->debutMs)->toBe(1_200_000);
});

// Critère vidéos longues : morceaux résumés puis synthèse, horodatages repris des paragraphes réels.
it('résume une vidéo de 28 minutes en morceaux puis en synthèse', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscription($utilisateur, 1680, resumeParagraphesFixture());

    resumeFaux()
        ->repondre(resumeReponse(resumeSortieMorceau(1)))
        ->repondre(resumeReponse(resumeSortieMorceau(31)))
        ->repondre(resumeReponse(resumeSortieMorceau(61)))
        ->repondre(resumeReponse([
            'resume' => 'La vidéo présente un balcon de culture. Elle couvre les bacs, l’arrosage et les plantes. Elle parle aussi des maladies. Elle se termine par des questions.',
            'points_cles' => [
                ['texte' => 'Choisir un balcon bien orienté.', 'paragraphe' => 2],
                ['texte' => 'Préférer des bacs légers.', 'paragraphe' => 33],
                ['texte' => 'Arroser au doigt.', 'paragraphe' => 62],
                ['texte' => 'Surveiller les pucerons.', 'paragraphe' => 70],
                ['texte' => 'Récolter feuille à feuille.', 'paragraphe' => 80],
            ],
            'chapitres' => [
                ['titre' => 'Le choix du balcon', 'paragraphe' => 1, 'resume' => 'L’orientation compte plus que le matériel. Elle décide des plantes possibles.'],
                ['titre' => 'Les bacs et le terreau', 'paragraphe' => 31, 'resume' => 'Les bacs doivent être légers et bien drainés. Le terreau se choisit avec soin.'],
                ['titre' => 'L’arrosage et les maladies', 'paragraphe' => 61, 'resume' => 'L’arrosage se fait au doigt. Les maladies se repèrent sur le revers des feuilles.'],
            ],
        ]));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    $sortie->refresh();
    $requetes = resumeFaux()->requetes;

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($requetes)->toHaveCount(4)
        ->and($requetes[3]->donnees)->toHaveCount(3)
        ->and($requetes[3]->instructions)->toContain('Fusionne')
        ->and(array_column($sortie->contenu['chapitres'], 'debut_ms'))->toBe([0, 600000, 1200000])
        // Paragraphe N commence à (N - 1) × 20 s : 2 → 20 s, 33 → 640 s, 62 → 1 220 s, 70 → 1 380 s, 80 → 1 580 s.
        ->and(array_column($sortie->contenu['points'], 'debut_ms'))->toBe([20000, 640000, 1220000, 1380000, 1580000])
        ->and(resumeCreditsConsommes($utilisateur))->toBe(1);
});

// Critère : un délai ou une erreur sur un morceau arrête la génération sans décompte.
it('arrête la génération d’une vidéo longue sans décompte si un morceau échoue', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscription($utilisateur, 1680, resumeParagraphesFixture());

    resumeFaux()->repondre(resumeReponse(resumeSortieMorceau(1)))->repondre(new ErreurModele(MotifErreurModele::Delai));

    $sortie = resumeLancer($utilisateur, $transcription);
    resumeGenerer($sortie);

    expect($sortie->fresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(resumeFaux()->requetes)->toHaveCount(2)
        ->and(resumeCreditsConsommes($utilisateur))->toBe(0);
});
