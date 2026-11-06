<?php

use App\Services\Traduction\DecoupeSousTitres;

// Découpe des traductions en sous-titres (cahier F4 : 42 caractères, 2 lignes, 1 à 7 secondes).

it('redécoupe une traduction trop longue en sous-titres qui restent dans les bornes du segment', function () {
    $texte = 'Der Balkon bekommt im Sommer sehr viel Sonne, deshalb wachsen dort Tomaten und Kräuter besonders gut, auch ohne Garten.';

    $sousTitres = app(DecoupeSousTitres::class)->sousTitres(10_000, 13_000, $texte);

    expect(count($sousTitres))->toBeGreaterThan(1)
        ->and(collect($sousTitres)->min(fn ($s) => $s->debutMs))->toBe(10_000)
        ->and(collect($sousTitres)->max(fn ($s) => $s->finMs))->toBe(13_000);

    foreach ($sousTitres as $index => $sousTitre) {
        expect($sousTitre->debutMs)->toBeGreaterThanOrEqual(10_000)
            ->and($sousTitre->finMs)->toBeLessThanOrEqual(13_000)
            ->and($sousTitre->finMs)->toBeGreaterThan($sousTitre->debutMs)
            ->and(count($sousTitre->lignes))->toBeLessThanOrEqual(2);

        if ($index > 0) {
            expect($sousTitre->debutMs)->toBeGreaterThanOrEqual($sousTitres[$index - 1]->finMs);
        }

        foreach ($sousTitre->lignes as $ligne) {
            expect(mb_strlen($ligne))->toBeLessThanOrEqual(42);
        }
    }

    // Aucun mot perdu ni coupé : la suite des mots est celle du texte d'origine.
    $mots = collect($sousTitres)->flatMap(fn ($s) => explode(' ', implode(' ', $s->lignes)))->implode(' ');
    expect($mots)->toBe($texte);
});

it('garde un segment court en un seul sous-titre, sans changer ses bornes', function () {
    $sousTitres = app(DecoupeSousTitres::class)->sousTitres(2_000, 4_000, 'Kurzer Garten.');

    expect($sousTitres)->toHaveCount(1)
        ->and($sousTitres[0]->debutMs)->toBe(2_000)
        ->and($sousTitres[0]->finMs)->toBe(4_000);
});

it('signale un texte qui ne peut pas tenir dans la durée, sans le couper', function () {
    $decoupe = app(DecoupeSousTitres::class);

    // 2 s : deux sous-titres au plus de 1 s chacun, soit 168 caractères au plus.
    expect($decoupe->tropLong(str_repeat('wort ', 30), 0, 2_000))->toBeFalse()
        ->and($decoupe->tropLong(str_repeat('wort ', 60), 0, 2_000))->toBeTrue()
        ->and($decoupe->tropLong(str_repeat('x', 50), 0, 7_000))->toBeTrue()
        ->and($decoupe->capacite(0, 2_000))->toBe(168);
});
