<?php

use App\Data\MotTranscrit;
use App\Data\TranscriptionBrute;
use App\Services\Transcription\ParagraphBuilder;
use App\Services\Transcription\ReponseElevenLabs;

// Regroupement en paragraphes (F2). Réponses au format Scribe, lues par ReponseElevenLabs.
// Fixtures : tests/fixtures/elevenlabs (voir avancement, point P22 pour l'origine).

/** Transcription neutre issue d'une fixture. */
function transcriptionFixture(string $nom): TranscriptionBrute
{
    $json = json_decode(file_get_contents(base_path('tests/fixtures/elevenlabs/'.$nom)), true, 512, JSON_THROW_ON_ERROR);

    return ReponseElevenLabs::versTranscription($json, 0);
}

/** Mots à intervalles réguliers, avec une pause optionnelle après un mot. @return list<MotTranscrit> */
function motsAvecPauses(array $texteEtPauses): array
{
    $mots = [];
    $t = 0;

    foreach ($texteEtPauses as [$texte, $pauseMs]) {
        $mots[] = new MotTranscrit($texte, $t, $t + 300);
        $t += 300 + 100 + $pauseMs;
    }

    return $mots;
}

/** Texte de paragraphes, espaces normalisés, pour comparer à celui du moteur. */
function textesJoints(array $paragraphes): string
{
    return preg_replace('/\s+/u', ' ', implode(' ', array_map(fn ($p) => $p->texte, $paragraphes)));
}

// Critères : coupure à la fin de phrase, à la pause et à la longueur maximale ;
// le paragraphe porte l'horodatage de son premier mot ; ponctuation du moteur conservée.

test('français : coupures à la pause et à la fin de phrase, horodatage du premier mot', function () {
    $transcription = transcriptionFixture('francais_redige.json');
    $paragraphes = (new ParagraphBuilder)->construire($transcription->mots);

    // Fin de phrase à 23 mots (seuil 20 atteint) ; pause de 1,5 s après « majuscules. » ; fin de fichier.
    expect(array_map(fn ($p) => count(explode(' ', $p->texte)), $paragraphes))->toBe([23, 27, 17]);

    // Chaque paragraphe commence au premier mot qu'il contient.
    expect($paragraphes[0]->debutMs)->toBe($transcription->mots[0]->debutMs)
        ->and($paragraphes[1]->debutMs)->toBe($transcription->mots[23]->debutMs)
        ->and($paragraphes[2]->debutMs)->toBe($transcription->mots[50]->debutMs);

    expect($paragraphes[0]->texte)->toEndWith('sans rien installer.')
        ->and($paragraphes[1]->texte)->toEndWith('avec des majuscules.')
        ->and($paragraphes[2]->texte)->toStartWith('Chaque paragraphe');
});

test('anglais : texte fidèle au moteur, ponctuation et majuscules inchangées', function () {
    $transcription = transcriptionFixture('anglais_redige.json');
    $paragraphes = (new ParagraphBuilder)->construire($transcription->mots);

    expect(array_map(fn ($p) => count(explode(' ', $p->texte)), $paragraphes))->toBe([25, 31, 3])
        ->and(textesJoints($paragraphes))->toBe($transcription->texte)
        ->and($paragraphes[1]->texte)->toStartWith('No installation is needed.');
});

test('discours sans pause : coupure de longueur maximale, aucun mot perdu', function () {
    $transcription = transcriptionFixture('discours_sans_pause_redige.json');
    $max = (int) config('voxia.paragraphes.max_mots');
    $paragraphes = (new ParagraphBuilder)->construire($transcription->mots);

    expect(count($paragraphes))->toBeGreaterThan(2)
        ->and(max(array_map(fn ($p) => count(explode(' ', $p->texte)), $paragraphes)))->toBe($max)
        ->and(textesJoints($paragraphes))->toBe($transcription->texte);

    // Les paragraphes se suivent dans l'ordre de la vidéo.
    $debuts = array_map(fn ($p) => $p->debutMs, $paragraphes);
    expect($debuts)->toBe(array_values(array_unique($debuts)))
        ->and($debuts)->toBe(collect($debuts)->sort()->values()->all());
});

test('vidéo muette : aucun mot, aucun paragraphe', function () {
    $transcription = transcriptionFixture('muette_enregistree.json');

    expect($transcription->mots)->toBe([])
        ->and((new ParagraphBuilder)->construire($transcription->mots))->toBe([]);
});

test('une pause égale au seuil coupe le paragraphe, une pause plus courte ne coupe pas', function () {
    $seuil = (int) config('voxia.paragraphes.pause_ms');

    $coupe = (new ParagraphBuilder)->construire(motsAvecPauses([['Un', 0], ['deux', $seuil - 100], ['trois', 0]]));
    expect($coupe)->toHaveCount(2);

    $pasCoupe = (new ParagraphBuilder)->construire(motsAvecPauses([['Un', 0], ['deux', $seuil - 101], ['trois', 0]]));
    expect($pasCoupe)->toHaveCount(1);
});

test('une fin de phrase ne coupe pas avant le minimum de mots', function () {
    $minimum = (int) config('voxia.paragraphes.fin_de_phrase_min_mots');

    $mots = [['Court.', 0]];
    for ($i = 1; $i < $minimum + 2; $i++) {
        $mots[] = ['mot'.$i, 0];
    }

    $paragraphes = (new ParagraphBuilder)->construire(motsAvecPauses($mots));

    expect($paragraphes)->toHaveCount(1);
});

test('la ponctuation isolée reste collée au mot précédent, les majuscules du moteur sont conservées', function () {
    $mots = motsAvecPauses([['Bonjour', 0], ['à', 0], ['tous', 0], ['!', 0], ['Voxia', 0], ['ici.', 0]]);
    $paragraphes = (new ParagraphBuilder)->construire($mots);

    expect($paragraphes)->toHaveCount(1)
        ->and($paragraphes[0]->texte)->toBe('Bonjour à tous! Voxia ici.');
});
