<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Page d'accueil (maquette Signal, docs/maquette-signal.html). Un test par critère de la liste de
// corrections : carte Créateurs, prix en dollars lus dans config/voxia.php, fiche technique, liens, partage.

beforeEach(fn () => app()->setLocale('fr'));

// Blade échappe les apostrophes du texte (d'une → d&#039;une) : on compare le HTML décodé.
function pageAccueilDecodee(string $adresse): string
{
    return html_entity_decode(test()->get($adresse)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

it('affiche la carte Créateurs « Une transcription, quatre formats. » avec les formats réels', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('Une transcription, quatre formats.')
        ->toContain('.txt')
        ->toContain('.pdf')
        ->toContain('.srt .vtt')
        ->toContain('.mp3')
        ->not->toContain('Une vidéo, quatre contenus');
});

it('garde la carte Marketeurs telle quelle, avec l\'analyse de script', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('Le script d\'une pub, découpé.')
        ->toContain('Analyse de scripts');
});

it('affiche les prix en dollars US lus dans config/voxia.php', function () {
    $html = pageAccueilDecodee('/fr');

    // Gratuit 0 $, Pro 12 $ par mois ou 96 $ par an, Pack 5 $.
    expect($html)
        ->toContain("0\u{00A0}$")
        ->toContain("12\u{00A0}$")
        ->toContain("96\u{00A0}$")
        ->toContain("5\u{00A0}$")
        ->not->toContain('€')
        ->not->toContain('[PRIX]');
});

it('reprend les quotas du plan Gratuit et du Pro depuis la configuration', function () {
    config([
        'voxia.plans.free.videos_per_month' => 7,
        'voxia.plans.free.max_video_seconds' => 900,
        'voxia.plans.pro.hours_per_month' => 42,
        'voxia.plans.pack.hours' => 15,
    ]);

    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('7 vidéos par mois')
        ->toContain('Jusqu\'à 15 minutes par vidéo')
        ->toContain('42 h par mois')
        ->toContain('15 h, valables 6 mois');
});

it('bascule le Pro entre mensuel et annuel sans rechargement', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('x-data="{ periode: \'mois\' }"')
        ->toContain('aria-pressed')
        ->toContain('/ mois')
        ->toContain('/ an');
});

it('présente le MP3 comme inclus dans tous les plans', function () {
    $html = pageAccueilDecodee('/fr');

    expect(substr_count($html, 'MP3 inclus'))->toBe(3);
});

it('liste les formats acceptés et les langues depuis la configuration', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('MP3, M4A, WAV, MP4, MOV, WEBM')
        ->toContain('FR · EN · ES · DE · IT · PT');
});

it('marque à confirmer la durée de conservation tant qu\'elle n\'est pas tranchée', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('[À CONFIRMER]')
        ->toContain('MP3 et fichiers importés gardés 30 jours');
});

it('envoie le lien du héros à la route qui démarre le parcours', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('action="'.route('lien.store').'"')
        ->toContain('name="lien"')
        ->toContain('Transcrire');
});

it('pointe le lien de dépôt de fichier vers l\'inscription pour un visiteur', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)->toContain('href="'.route('register').'"');
});

it('branche les liens de l\'en-tête et du pied de page', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('href="'.route('accueil').'#usages"')
        ->toContain('href="'.route('accueil').'#fonctionnement"')
        ->toContain('href="'.route('accueil').'#tarifs"')
        ->toContain('href="'.route('login').'"')
        ->toContain('Essayer gratuitement')
        ->toContain('href="'.route('confidentialite').'"')
        ->toContain('href="'.route('conditions').'"')
        ->toContain('href="'.route('contact').'"');
});

// Les textes juridiques sont des brouillons : leurs espaces à compléter restent visibles (CLAUDE.md §7).
it('répond aux pages du pied de page, sans texte juridique inventé', function (string $route, string $titre, string $espace) {
    $this->get($route)
        ->assertOk()
        ->assertSee($titre)
        ->assertSee($espace);
})->with([
    'confidentialité' => ['/fr/confidentialite', 'Confidentialité', '[À CONFIRMER]'],
    'conditions' => ['/fr/conditions', 'Conditions', '[À CONFIRMER]'],
    'contact' => ['/fr/contact', 'Contact', '[EMAIL DE CONTACT À COMPLÉTER]'],
]);

it('donne à la page un titre, une description et des balises de partage au style Surligneur', function () {
    $html = pageAccueilDecodee('/fr');

    expect($html)
        ->toContain('<title>Voxia — Chaque vidéo contient un texte</title>')
        ->toContain('<meta name="description"')
        ->toContain('property="og:title"')
        ->toContain('property="og:description"')
        ->toContain('property="og:image" content="'.asset('images/partage-voxia.png').'"')
        ->toContain('property="og:image:width" content="1200"')
        ->toContain('name="twitter:card" content="summary_large_image"')
        ->toContain('name="theme-color" content="#F4F1EA"');

    expect(file_exists(public_path('images/partage-voxia.png')))->toBeTrue();
});

it('coupe toutes les animations de la page sous prefers-reduced-motion', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Les blocs de coupure doivent couvrir chaque classe d'animation de la page.
    preg_match_all('/@media \(prefers-reduced-motion: reduce\)\s*\{(.*?)\n\}/s', $css, $blocs);
    $coupures = implode("\n", $blocs[1]);

    expect($coupures)->not->toBeEmpty();

    foreach (['.vx-onde-barre', '.vx-pulsation', '.vx-defilement', '.vx-paquet', '.vx-curseur', '.vx-frappe'] as $classe) {
        expect($coupures)->toContain($classe);
    }
});

it('affiche la page d\'accueil sans barre de défilement horizontale par construction', function () {
    $html = pageAccueilDecodee('/fr');

    // Le bandeau et la ligne de process sont dans un conteneur qui coupe le débordement.
    expect($html)
        ->toContain('overflow-hidden border-b border-filet py-[18px]')
        ->toContain('overflow-hidden px-4 text-center');
});
