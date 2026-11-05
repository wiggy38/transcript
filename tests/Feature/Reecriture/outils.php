<?php

// Outils partagés des tests de la réécriture (V2). Chargés par require_once depuis chaque fichier de test.
// Les outils du résumé (faux modèle, utilisateurs, transcriptions) sont réutilisés.

require_once __DIR__.'/../Resume/outils.php';

use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Enums\TonReecriture;
use App\Enums\UniteConsommation;
use App\Jobs\GenererReecriture;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Reecriture\LancerReecriture;
use App\Services\Reecriture\ServiceReecriture;

/** @param  list<FormatReecriture>  $formats  @return array<string, AiOutput> */
function reecritureLancer(User $utilisateur, Transcription $transcription, array $formats, bool $regenerer = false, bool $confirme = false): array
{
    return app(LancerReecriture::class)->lancer(
        $utilisateur,
        $transcription,
        $formats,
        TonReecriture::Professionnel,
        LongueurReecriture::Moyenne,
        'fr',
        $regenerer,
        $confirme,
    );
}

/** Exécute la tâche comme la file le fait : la tâche appelle le service avec la sortie. */
function reecritureGenerer(AiOutput $sortie): void
{
    (new GenererReecriture($sortie->getKey()))->handle(app(ServiceReecriture::class));
}

function reecritureCreditsConsommes(User $utilisateur): int
{
    return (int) UsageRecord::where('user_id', $utilisateur->getKey())
        ->where('unite', UniteConsommation::CreditIa)
        ->where('motif', 'reecriture')
        ->sum('quantite');
}

/** Une suite de mots sans chiffre, de longueur donnée. */
function reecritureMots(int $nombre, string $mot = 'balcon'): string
{
    return implode(' ', array_fill(0, $nombre, $mot));
}

/** Article de 1 069 mots : dans la fourchette « moyenne » (1 000 à 1 250). */
function reecritureArticleValide(): array
{
    return [
        'titre' => 'Un balcon qui produit',
        'introduction' => 'Le balcon sud reçoit beaucoup de soleil en été. '.reecritureMots(40),
        'intertitres' => [
            ['titre' => 'Choisir le bon emplacement', 'paragraphes' => [reecritureMots(320)]],
            ['titre' => 'Choisir les bacs', 'paragraphes' => [reecritureMots(320)]],
            ['titre' => 'Arroser sans se tromper', 'paragraphes' => [reecritureMots(320)]],
        ],
        'conclusion' => 'Un balcon bien pensé donne des récoltes régulières. '.reecritureMots(40),
    ];
}

/** Newsletter sans chiffre ; l'objet et l'aperçu respectent leurs limites. */
function reecritureNewsletterValide(string $appelAction = 'Essaie ce gabarit ce week-end.'): array
{
    return [
        'objet' => 'Un balcon qui produit',
        'apercu' => 'Trois gestes simples pour des récoltes régulières.',
        'accroche' => 'Le balcon sud reçoit beaucoup de soleil.',
        'corps' => [
            'Les bacs doivent être légers, avec un bon drainage.',
            'J’arrose au doigt plutôt qu’au calendrier.',
            'La tomate demande un tuteur solide dès la plantation.',
        ],
        'appel_action' => $appelAction,
    ];
}

/** Post LinkedIn court : accroche et trois paragraphes, sous 1 300 caractères. */
function reecritureLinkedinValide(): array
{
    return [
        'accroche' => 'Un balcon réussi commence par la lumière.',
        'paragraphes' => [
            'Le balcon sud reçoit beaucoup de soleil.',
            'Les bacs légers évitent les gros efforts.',
            'Arroser au doigt évite les excès.',
        ],
    ];
}

/** Thread de six messages, chacun sous 274 caractères (la limite de 280 moins le numéro). */
function reecritureThreadValide(): array
{
    return [
        'messages' => array_map(
            fn (string $texte) => ['texte' => $texte],
            [
                'Mon balcon sud en trois leçons. Un fil.',
                'Le soleil compte plus que la surface : le sud reçoit beaucoup de lumière.',
                'Les bacs doivent être légers, avec un bon drainage au fond.',
                'J’arrose au doigt plutôt qu’au calendrier. La terre dit ce dont elle a besoin.',
                'La tomate demande un tuteur solide dès la plantation.',
                'Ressources et détails dans ma newsletter, lien en bio.',
            ],
        ),
    ];
}
