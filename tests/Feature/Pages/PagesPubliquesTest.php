<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CleTraduction;

uses(RefreshDatabase::class);

// Pages publiques : tarifs, questions fréquentes, conditions, confidentialité (style Signal, fr et en).
// Un test par critère de la section « Pages publiques » de docs/avancement.md.

// Blade échappe les apostrophes (d'une → d&#039;une) : on compare le HTML décodé.
function pageDecodee(string $adresse): string
{
    return html_entity_decode(test()->get($adresse)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

it('affiche les pages tarifs, questions, conditions et confidentialité dans les deux langues sans clé visible', function (string $page) {
    foreach (['fr', 'en'] as $langue) {
        $html = test()->get("/{$langue}/{$page}")->assertOk()->getContent();

        expect($html)->toContain('<html lang="'.$langue.'"');
        expect(CleTraduction::visible($html))->toBeNull();
    }
})->with(['tarifs', 'questions', 'conditions', 'confidentialite']);

it('affiche le bandeau BROUILLON en tête des conditions et de la confidentialité', function (string $page) {
    $html = pageDecodee("/fr/{$page}");

    expect(strpos($html, 'BROUILLON — à faire relire par un juriste avant lancement'))
        ->toBeInt()
        ->toBeLessThan(strpos($html, '<h1'));
})->with(['conditions', 'confidentialite']);

it('garantit dans les conditions que l\'utilisateur a les droits sur les vidéos transcrites', function () {
    $html = pageDecodee('/fr/conditions');

    expect($html)
        ->toContain('Tu garantis que tu disposes de tous les droits nécessaires')
        ->toContain('Voxia ne vérifie pas ces droits');
});

it('affirme dans les conditions qu\'aucune transcription n\'est publique', function () {
    $html = pageDecodee('/fr/conditions');

    expect($html)->toContain('Aucune transcription n\'est publique');
});

it('reprend dans les conditions les durées de conservation lues dans la configuration', function () {
    config(['voxia.plans.free.history_days' => 45, 'voxia.retention.media_days' => 60]);

    $html = pageDecodee('/fr/conditions');

    expect($html)
        ->toContain('conservées 45 jours')
        ->toContain('conservés 60 jours');
});

it('liste dans la confidentialité les sous-traitants et les transferts hors Union européenne', function () {
    $html = pageDecodee('/fr/confidentialite');

    expect($html)
        ->toContain('ElevenLabs')
        ->toContain('Anthropic')
        ->toContain('Paddle')
        ->toContain('Hébergeur')
        ->toContain('stockage des fichiers')
        ->toContain('envoi des emails')
        ->toContain('Transferts hors de l\'Union européenne'); // en-tête de section, décodé
});

it('décrit dans la confidentialité les droits RGPD, dont l\'export et la suppression', function () {
    $html = pageDecodee('/fr/confidentialite');

    expect($html)
        ->toContain('accès à tes données')
        ->toContain('effacement')
        ->toContain('portabilité')
        ->toContain('ZIP')
        ->toContain('cnil.fr');
});

it('identifie l\'éditeur avec le nom, le SIRET et l\'adresse, et garde les espaces juridiques à compléter', function () {
    config(['voxia.contact.email' => null]);

    $html = pageDecodee('/fr/confidentialite');

    expect($html)
        ->toContain('EVANOE')
        ->toContain('919 260 000 00014')
        ->toContain('13 rue de la Tuilerie, Betouille, 77950 Rubelles')
        ->toContain('forme juridique [À CONFIRMER]')
        ->toContain('[EMAIL DE CONTACT À COMPLÉTER]');
});

it('affiche l\'adresse de contact dans les conditions quand elle est configurée', function () {
    config(['voxia.contact.email' => 'equipe@voxia.test']);

    expect(pageDecodee('/fr/conditions'))->toContain('equipe@voxia.test');
});

it('n\'ajoute ni outil de mesure d\'audience ni bandeau de consentement', function () {
    $html = pageDecodee('/fr/tarifs');

    expect($html)
        ->not->toContain('googletagmanager')
        ->not->toContain('gtag(')
        ->not->toContain('plausible')
        ->not->toContain('matomo')
        ->not->toContain('tarteaucitron');
});

it('affiche sur la page Tarifs les prix et les quotas lus dans la configuration', function () {
    config([
        'voxia.plans.free.videos_per_month' => 7,
        'voxia.plans.pro.hours_per_month' => 42,
        'voxia.plans.pack.hours' => 15,
        'voxia.plans.pro.price_monthly_usd' => 14,
    ]);

    $html = pageDecodee('/fr/tarifs');

    expect($html)
        ->toContain('7 vidéos par mois')
        ->toContain('42 h par mois')
        ->toContain('15 h, valables 6 mois')
        ->toContain("14\u{00A0}$ par mois")
        ->toContain("5\u{00A0}$, paiement unique")
        ->not->toContain('Business');
});

it('affiche sur la page Tarifs les formats et la durée maximale par plan', function () {
    $html = pageDecodee('/en/tarifs');

    expect($html)
        ->toContain('TXT, SRT')
        ->toContain('TXT, PDF, SRT, VTT')
        ->toContain('10 min (preview beyond)')
        ->toContain('Unlimited');
});

it('affiche sur la page Questions fréquentes les formats et les langues lus dans la configuration', function () {
    $html = pageDecodee('/fr/questions');

    expect($html)
        ->toContain(strtoupper(implode(', ', config('voxia.uploads.extensions'))))
        ->toContain('français, anglais');
});

it('ajoute les liens Tarifs et Questions fréquentes au pied de page', function () {
    $html = pageDecodee('/fr');

    expect($html)
        ->toContain('href="'.route('tarifs', ['locale' => 'fr']).'"')
        ->toContain('href="'.route('questions', ['locale' => 'fr']).'"');
});
