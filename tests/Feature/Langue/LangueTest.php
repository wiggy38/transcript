<?php

use App\Mail\AlerteEchecsTelechargement;
use App\Mail\CompteSupprime;
use App\Models\User;
use App\Support\Formats;
use App\Support\Langue;
use App\Support\Montant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Aplatit un tableau de traductions : « accueil.hero.titre » => valeur. */
function clesAplaties(array $tableau, string $prefixe = ''): array
{
    $resultat = [];

    foreach ($tableau as $cle => $valeur) {
        $chemin = $prefixe === '' ? (string) $cle : $prefixe.'.'.$cle;

        if (is_array($valeur)) {
            $resultat += clesAplaties($valeur, $chemin);
        } else {
            $resultat[$chemin] = $valeur;
        }
    }

    return $resultat;
}

/** Espaces fines (insécables ou non) ramenées à une espace simple, pour comparer les nombres. */
function espaces(string $texte): string
{
    return str_replace(["\u{202F}", "\u{00A0}"], ' ', $texte);
}

/**
 * Groupes de traductions (un par fichier de lang). Une clé qui s'affiche telle quelle dans une
 * page (« accueil.hero.titre ») signale une traduction manquante.
 */
function groupesDeTraduction(): array
{
    return array_map(fn (string $fichier) => basename($fichier, '.php'), glob(lang_path('en/*.php')));
}

/** Première clé de traduction restée visible dans le HTML, ou null. */
function cleVisible(string $html): ?string
{
    $groupes = implode('|', array_map('preg_quote', groupesDeTraduction()));

    return preg_match('/\b(?:'.$groupes.')\.[a-z_][a-z0-9_]*\.[a-z0-9_.]+/', $html, $trouve) === 1 ? $trouve[0] : null;
}

function pasDeCleVisible(string $html): void
{
    expect(cleVisible($html))->toBeNull();
}

// Garde-fou : le détecteur repère une clé restée visible, et ne signale pas une phrase normale.
it('repère une clé de traduction restée visible dans une page', function () {
    expect(cleVisible('<p>accueil.hero.titre</p>'))->toBe('accueil.hero.titre')
        ->and(cleVisible('<p>Voxia writes it down. Usages, tarifs.</p>'))->toBeNull();
});

// Critère : toutes les clés existent dans les deux langues, aucune valeur vide.
it('garde les mêmes clés en français et en anglais', function () {
    foreach (glob(lang_path('fr/*.php')) as $fichier) {
        $nom = basename($fichier);
        $fr = clesAplaties(require $fichier);
        $en = clesAplaties(require lang_path('en/'.$nom));

        expect(array_keys(array_diff_key($fr, $en)))->toBe([], "Clés absentes en anglais dans $nom")
            ->and(array_keys(array_diff_key($en, $fr)))->toBe([], "Clés absentes en français dans $nom");
    }
});

it('ne laisse aucune traduction vide', function () {
    foreach (['fr', 'en'] as $langue) {
        foreach (glob(lang_path($langue.'/*.php')) as $fichier) {
            foreach (clesAplaties(require $fichier) as $cle => $valeur) {
                if (is_string($valeur)) {
                    expect(trim($valeur))->not->toBe('', "$langue : $cle est vide");
                }
            }
        }
    }
});

// Critère : chaque page principale s'affiche dans les deux langues, sans clé manquante.
it('affiche les pages publiques dans les deux langues sans clé manquante', function (string $page) {
    foreach (['fr', 'en'] as $langue) {
        $adresse = str_replace('{langue}', $langue, $page);
        $html = test()->get($adresse)->assertOk()->getContent();

        expect($html)->toContain('<html lang="'.$langue.'"');
        pasDeCleVisible($html);
    }
})->with([
    'accueil' => '/{langue}',
    'confidentialité' => '/{langue}/confidentialite',
    'conditions' => '/{langue}/conditions',
    'contact' => '/{langue}/contact',
]);

it('affiche les pages de l\'application dans la langue du compte, sans clé manquante', function (string $page) {
    foreach (['fr', 'en'] as $langue) {
        $utilisateur = User::factory()->create(['locale' => $langue]);

        $html = test()->actingAs($utilisateur)->get($page)->assertOk()->getContent();

        expect($html)->toContain('<html lang="'.$langue.'"');
        pasDeCleVisible($html);
    }
})->with([
    'tableau de bord' => '/tableau-de-bord',
    'historique' => '/historique',
    'réglages' => '/reglages',
    'abonnement' => '/abonnement',
]);

it('affiche les écrans de connexion et d\'inscription sans clé manquante', function (string $page) {
    foreach (['fr', 'en'] as $langue) {
        $html = test()->withCookie(Langue::COOKIE, $langue)->get(route($page))->assertOk()->getContent();

        expect($html)->toContain('<html lang="'.$langue.'"');
        pasDeCleVisible($html);
    }
})->with(['connexion' => 'login', 'inscription' => 'register']);

// Critère : la racine suit la langue du visiteur ; le compte, puis le navigateur, puis l'anglais.
it('renvoie la racine vers la langue du navigateur, sinon vers l\'anglais', function () {
    test()->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')->get('/')->assertRedirect('/fr');
    test()->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/')->assertRedirect('/en');
    test()->get('/')->assertRedirect('/en');
});

it('donne la priorité à la préférence du compte sur le navigateur', function () {
    $utilisateur = User::factory()->create(['locale' => 'en']);

    test()->actingAs($utilisateur)
        ->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/tableau-de-bord')
        ->assertOk()
        ->assertSee('<html lang="en"', false);
});

it('suit le navigateur quand le compte n\'a pas de préférence', function () {
    $utilisateur = User::factory()->create(['locale' => null]);

    test()->actingAs($utilisateur)
        ->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/tableau-de-bord')
        ->assertOk()
        ->assertSee('<html lang="fr"', false);
});

it('garde l\'adresse /fr ou /en plus forte que le navigateur et mémorise le choix', function () {
    test()->withHeader('Accept-Language', 'fr')->get('/en/contact')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertCookie(Langue::COOKIE, 'en');
});

it('lit la langue du navigateur avec ses priorités', function (?string $entete, ?string $attendue) {
    expect(Langue::depuisNavigateur($entete))->toBe($attendue);
})->with([
    ['fr-CH, fr;q=0.9, en;q=0.8', 'fr'],
    ['de, en-US;q=0.9, fr;q=0.5', 'en'],
    ['de-DE', null],
    ['', null],
    [null, null],
]);

// Critère : le sélecteur enregistre le choix (navigateur et compte) et rouvre la même page.
it('change de langue depuis le sélecteur et renvoie sur la même page', function () {
    $utilisateur = User::factory()->create(['locale' => 'en']);

    test()->actingAs($utilisateur)
        ->post('/langue', ['locale' => 'fr', 'retour' => '/tableau-de-bord?x=1'])
        ->assertRedirect(url('/tableau-de-bord?x=1'))
        ->assertCookie(Langue::COOKIE, 'fr');

    expect($utilisateur->fresh()->locale)->toBe('fr');
});

it('passe d\'une adresse publique à l\'autre langue', function () {
    test()->post('/langue', ['locale' => 'fr', 'retour' => '/en/contact'])
        ->assertRedirect(url('/fr/contact'));
});

it('ne suit jamais un retour externe', function () {
    test()->post('/langue', ['locale' => 'fr', 'retour' => 'https://exemple.invalide/pirate'])
        ->assertRedirect(route('accueil', ['locale' => 'fr']));
});

it('refuse une langue absente de l\'application', function () {
    test()->post('/langue', ['locale' => 'de'])->assertSessionHasErrors('locale');
});

it('efface la préférence du compte avec « suivre le navigateur »', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);

    test()->actingAs($utilisateur)
        ->put('/reglages/langue', ['locale' => 'auto'])
        ->assertRedirect(route('reglages'));

    expect($utilisateur->fresh()->locale)->toBeNull();
});

// Critère : hreflang sur chaque page publique.
it('déclare les adresses de chaque langue pour les moteurs de recherche', function () {
    $html = test()->get('/fr')->assertOk()->getContent();

    expect($html)
        ->toContain('<link rel="alternate" hreflang="fr" href="'.url('/fr').'">')
        ->toContain('<link rel="alternate" hreflang="en" href="'.url('/en').'">')
        ->toContain('<link rel="alternate" hreflang="x-default" href="'.url('/').'">');
});

// Critère : dates, nombres et devises selon la langue.
it('écrit les dates selon la langue', function () {
    $date = Carbon::parse('2026-11-04 10:30');

    app()->setLocale('fr');
    expect(Formats::date($date))->toBe('4 novembre 2026')
        ->and(Formats::dateHeure($date))->toBe('4 novembre 2026 à 10:30');

    app()->setLocale('en');
    expect(Formats::date($date))->toBe('November 4, 2026')
        ->and(Formats::dateHeure($date))->toBe('November 4, 2026 at 10:30 AM');
});

it('écrit les nombres et les devises selon la langue', function () {
    app()->setLocale('fr');
    expect(espaces(Formats::nombre(1234.5, 1)))->toBe('1 234,5')
        ->and(espaces(Montant::dollars(12)))->toBe('12 $')
        ->and(espaces(Montant::dollars(12.5)))->toBe('12,50 $');

    app()->setLocale('en');
    expect(Formats::nombre(1234.5, 1))->toBe('1,234.5')
        ->and(Montant::dollars(12))->toBe('$12')
        ->and(Montant::dollars(12.5))->toBe('$12.50');
});

// Critère : les courriels partent dans la langue du destinataire.
it('envoie le courriel de suppression dans la langue du compte', function () {
    $fr = new CompteSupprime('Ada', 'fr');
    $en = new CompteSupprime('Ada', 'en');

    // assertHasSubject rend le courriel dans la langue du courriel, comme à l'envoi.
    $fr->assertHasSubject(__('courriels.suppression.objet', [], 'fr'));
    $en->assertHasSubject(__('courriels.suppression.objet', [], 'en'));
});

it('envoie l\'alerte à l\'administrateur dans sa langue', function () {
    $administrateur = User::factory()->create(['is_admin' => true, 'locale' => 'en']);

    $alerte = new AlerteEchecsTelechargement($administrateur, 0.2, 10, 2, 0.05, 60);

    $alerte->assertHasSubject(__('administration.courriel.alerte_echecs.objet', [], 'en'));
});
