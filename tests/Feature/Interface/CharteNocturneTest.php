<?php

use App\Enums\StatutTranscription;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;

/*
 * Charte « Surligneur » (cahier §7) : jetons des deux thèmes, contrastes, animations, polices
 * auto-hébergées, composants.
 */

function luminanceCharte(string $hex): float
{
    $canaux = array_map(
        fn (int $i) => hexdec(substr($hex, $i, 2)) / 255,
        [1, 3, 5],
    );
    [$r, $g, $b] = array_map(
        fn (float $c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
        $canaux,
    );

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function contrasteCharte(string $premier, string $second): float
{
    [$clair, $sombre] = [luminanceCharte($premier), luminanceCharte($second)];
    if ($clair < $sombre) {
        [$clair, $sombre] = [$sombre, $clair];
    }

    return ($clair + 0.05) / ($sombre + 0.05);
}

/**
 * @return array<string, string> jetons --vx-* de voxia-tokens.css pour le thème demandé ('clair' ou 'sombre')
 */
function jetonsCharte(string $theme): array
{
    $css = file_get_contents(resource_path('css/voxia-tokens.css'));
    $motif = $theme === 'sombre'
        ? '/:root\[data-theme="dark"\]\s*\{(.*?)\}/s'
        : '/:root,\s*:root\[data-theme="light"\]\s*\{(.*?)\}/s';
    preg_match($motif, $css, $bloc);

    preg_match_all('/--vx-([a-z0-9-]+):\s*(#[0-9A-Fa-f]{6});/', $bloc[1] ?? '', $correspondances, PREG_SET_ORDER);

    return collect($correspondances)->mapWithKeys(fn (array $m) => [$m[1] => strtoupper($m[2])])->all();
}

test('les textes de la charte atteignent 4,5:1 sur chaque fond, dans les deux thèmes', function () {
    foreach (['clair', 'sombre'] as $theme) {
        $jetons = jetonsCharte($theme);

        foreach (['text', 'text-2', 'text-3', 'accent-text', 'error', 'success', 'warning'] as $texte) {
            foreach (['bg', 'surface', 'surface-2'] as $fond) {
                expect(contrasteCharte($jetons[$texte], $jetons[$fond]))
                    ->toBeGreaterThanOrEqual(4.5, "$theme : $texte sur $fond");
            }
        }
    }
});

test('le texte des boutons atteint 4,5:1 sur l\'accent, dans les deux thèmes', function () {
    foreach (['clair', 'sombre'] as $theme) {
        $jetons = jetonsCharte($theme);

        expect(contrasteCharte($jetons['on-accent'], $jetons['accent']))
            ->toBeGreaterThanOrEqual(4.5, "$theme : texte de bouton sur accent");
    }
});

test('le mot surligné des titres atteint 4,5:1 sur son surlignage', function () {
    foreach (['clair', 'sombre'] as $theme) {
        $jetons = jetonsCharte($theme);

        expect(contrasteCharte($jetons['accent-word'], $jetons['accent-word-bg']))
            ->toBeGreaterThanOrEqual(4.5, "$theme : mot d'accent sur surlignage");
    }
});

test('les contours de champ et de bouton à filet atteignent 3:1 sur chaque fond', function () {
    foreach (['clair', 'sombre'] as $theme) {
        $jetons = jetonsCharte($theme);

        foreach (['bg', 'surface', 'surface-2'] as $fond) {
            expect(contrasteCharte($jetons['line-strong'], $jetons[$fond]))
                ->toBeGreaterThanOrEqual(3.0, "$theme : contour sur $fond");
        }
    }
});

test('l\'accent d\'action atteint 3:1 sur le fond, comme forme', function () {
    foreach (['clair', 'sombre'] as $theme) {
        $jetons = jetonsCharte($theme);

        expect(contrasteCharte($jetons['accent'], $jetons['bg']))
            ->toBeGreaterThanOrEqual(3.0, "$theme : accent sur fond");
    }
});

test('les couleurs viennent des jetons, jamais écrites en dur dans app.css', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect(preg_match('/#[0-9A-Fa-f]{3,6}\b/', $css))->toBe(0);
});

test('chaque animation est coupée sous prefers-reduced-motion', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toMatch(
        '/@media \(prefers-reduced-motion: reduce\)\s*\{\s*\.vx-onde-barre,\s*\.vx-pulsation,\s*\.vx-indeterminee\s*\{\s*animation:\s*none;\s*\}/',
    );
});

test('aucune police ni script n\'est chargé depuis un CDN externe', function () {
    $dossiers = [resource_path('css'), resource_path('js'), resource_path('views')];
    $interdits = ['fonts.googleapis', 'fonts.gstatic', 'fonts.bunny', 'use.typekit'];

    foreach ($dossiers as $dossier) {
        $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossier));
        foreach ($fichiers as $fichier) {
            if (! $fichier->isFile()) {
                continue;
            }
            $contenu = file_get_contents($fichier->getPathname());
            foreach ($interdits as $interdit) {
                expect(str_contains($contenu, $interdit))->toBeFalse($fichier->getPathname());
            }
        }
    }
});

test('le guide de style n\'est pas servi hors environnement local', function () {
    $this->get('/styleguide')->assertNotFound();
});

test('le guide de style affiche chaque composant et chaque statut', function () {
    $html = view('styleguide')->render();

    expect($html)
        ->toContain('role="progressbar"')
        ->toContain('aria-invalid="true"')
        ->toContain('id="fenetre-demo"')
        ->toContain('vx-onde-barre')
        ->toContain('datetime="PT13.000S"')
        ->toContain('>00:13</time>');

    foreach (StatutTranscription::cases() as $statut) {
        expect($html)->toContain(__('interface.statuts.'.$statut->value));
    }
});

test('le badge de statut accepte l\'énumération ou sa valeur texte', function () {
    $depuisEnum = Blade::render('<x-badge-statut :statut="$statut" />', ['statut' => StatutTranscription::Echec]);
    $depuisTexte = Blade::render('<x-badge-statut statut="echec" />');

    expect($depuisEnum)->toContain(__('interface.statuts.echec'))
        ->and($depuisTexte)->toContain(__('interface.statuts.echec'));
});

test('le compteur de quota n\'écrit aucune valeur de plan en dur', function () {
    $html = Blade::render('<x-compteur-quota :utilise="3" :limite="5" />');

    expect($html)->toContain('aria-valuemax="5"')
        ->and($html)->toContain('aria-valuenow="3"');
});

test('les mises en page s\'affichent avec leur en-tête et leur pied', function () {
    URL::defaults(['locale' => 'fr']);
    $marketing = Blade::render('<x-layouts::marketing>contenu</x-layouts::marketing>');
    $application = Blade::render('<x-layouts::app actif="historique"><x-slot:quota>Q</x-slot:quota>contenu</x-layouts::app>');

    expect($marketing)->toContain(__('interface.navigation.fonctionnement'))->toContain(__('interface.pied.confidentialite'))
        ->and($application)->toContain(__('interface.application.nouvelle'))
        ->toContain(__('interface.application.historique'))
        ->toContain('aria-current="page"')
        ->toContain('Q');
});
