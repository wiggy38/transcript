<?php

namespace Tests\Support;

use App\Data\MotTranscrit;

/**
 * Contrôle un fichier SRT ou VTT règle par règle (cahier F4). Renvoie la liste des
 * écarts ; une liste vide signifie que le fichier respecte toutes les règles.
 *
 * Règles : UTF-8 sans BOM ; numérotation SRT séquentielle ; horodatages au bon format ;
 * début avant fin ; 1 à 7 secondes par sous-titre ; 1 ou 2 lignes ; 42 caractères au plus
 * par ligne ; aucun chevauchement ; et, si les mots sources sont fournis, aucune coupure
 * au milieu d'un mot et aucun mot perdu.
 */
final class ValidateurSousTitres
{
    private const SRT_HORODATAGE = '/^(\d{2}):(\d{2}):(\d{2}),(\d{3}) --> (\d{2}):(\d{2}):(\d{2}),(\d{3})$/';

    private const VTT_HORODATAGE = '/^(\d{2}):(\d{2}):(\d{2})\.(\d{3}) --> (\d{2}):(\d{2}):(\d{2})\.(\d{3})$/';

    private const PONCTUATION_SEULE = '/^[.,;:!?…)»”"\]]+$/u';

    /**
     * @param  list<MotTranscrit>|null  $mots  Mots sources, pour contrôler les coupures.
     * @return list<string>
     */
    public static function srt(string $contenu, ?array $mots = null): array
    {
        $erreurs = self::erreursCommunes($contenu);
        $blocs = self::blocs($contenu);

        $attendu = 1;
        $sousTitres = [];

        foreach ($blocs as $numeroBloc => $bloc) {
            $lignes = explode("\n", $bloc);

            if (($lignes[0] ?? '') !== (string) $attendu) {
                $erreurs[] = "Bloc {$numeroBloc} : numéro « ".($lignes[0] ?? '')." » attendu « {$attendu} ».";
            }
            $attendu++;

            $horodatage = $lignes[1] ?? '';
            if (preg_match(self::SRT_HORODATAGE, $horodatage, $m) !== 1) {
                $erreurs[] = "Bloc {$numeroBloc} : horodatage SRT invalide « {$horodatage} ».";

                continue;
            }

            $sousTitres[] = [
                'numero' => $numeroBloc,
                'debut' => self::enMs((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4]),
                'fin' => self::enMs((int) $m[5], (int) $m[6], (int) $m[7], (int) $m[8]),
                'lignes' => array_slice($lignes, 2),
            ];
        }

        return [...$erreurs, ...self::controlerSousTitres($sousTitres, $mots)];
    }

    /**
     * @param  list<MotTranscrit>|null  $mots  Mots sources, pour contrôler les coupures.
     * @return list<string>
     */
    public static function vtt(string $contenu, ?array $mots = null): array
    {
        $erreurs = self::erreursCommunes($contenu);

        $entete = strtok($contenu, "\n");
        if ($entete !== 'WEBVTT') {
            $erreurs[] = 'L’en-tête doit être « WEBVTT », trouvé « '.(string) $entete.' ».';
        }

        $corps = substr($contenu, strlen('WEBVTT'));
        $sousTitres = [];

        foreach (self::blocs(ltrim($corps, "\n")) as $numeroBloc => $bloc) {
            $lignes = explode("\n", $bloc);
            $horodatage = $lignes[0] ?? '';

            if (preg_match(self::VTT_HORODATAGE, $horodatage, $m) !== 1) {
                $erreurs[] = "Bloc {$numeroBloc} : horodatage VTT invalide « {$horodatage} ».";

                continue;
            }

            $sousTitres[] = [
                'numero' => $numeroBloc,
                'debut' => self::enMs((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4]),
                'fin' => self::enMs((int) $m[5], (int) $m[6], (int) $m[7], (int) $m[8]),
                'lignes' => array_slice($lignes, 1),
            ];
        }

        return [...$erreurs, ...self::controlerSousTitres($sousTitres, $mots)];
    }

    /** @return list<string> */
    private static function erreursCommunes(string $contenu): array
    {
        $erreurs = [];

        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $erreurs[] = 'Le fichier n’est pas de l’UTF-8 valide.';
        }

        if (str_starts_with($contenu, "\xEF\xBB\xBF")) {
            $erreurs[] = 'Le fichier commence par un BOM : il n’est pas attendu.';
        }

        if (str_contains($contenu, "\r")) {
            $erreurs[] = 'Fins de ligne CRLF : LF attendu.';
        }

        return $erreurs;
    }

    /**
     * @param  list<array{numero: int, debut: int, fin: int, lignes: list<string>}>  $sousTitres
     * @param  list<MotTranscrit>|null  $mots
     * @return list<string>
     */
    private static function controlerSousTitres(array $sousTitres, ?array $mots): array
    {
        $erreurs = [];
        $maxLignes = (int) config('voxia.subtitles.max_lines');
        $maxCaracteres = (int) config('voxia.subtitles.max_chars_per_line');
        $minMs = (int) config('voxia.subtitles.min_duration_seconds') * 1000;
        $maxMs = (int) config('voxia.subtitles.max_duration_seconds') * 1000;
        $precedentFin = null;

        foreach ($sousTitres as $st) {
            $n = $st['numero'];
            $duree = $st['fin'] - $st['debut'];

            if ($st['debut'] >= $st['fin']) {
                $erreurs[] = "Sous-titre {$n} : le début ne précède pas la fin.";
            }

            if ($duree < $minMs || $duree > $maxMs) {
                $erreurs[] = "Sous-titre {$n} : durée {$duree} ms hors de {$minMs} à {$maxMs} ms.";
            }

            if (count($st['lignes']) < 1 || count($st['lignes']) > $maxLignes) {
                $erreurs[] = "Sous-titre {$n} : ".count($st['lignes'])." ligne(s), {$maxLignes} au plus.";
            }

            foreach ($st['lignes'] as $ligne) {
                if (mb_strlen($ligne) > $maxCaracteres) {
                    $erreurs[] = "Sous-titre {$n} : ligne de ".mb_strlen($ligne)." caractères, {$maxCaracteres} au plus « {$ligne} ».";
                }
            }

            if ($precedentFin !== null && $st['debut'] < $precedentFin) {
                $erreurs[] = "Sous-titre {$n} : chevauchement avec le précédent.";
            }

            $precedentFin = $st['fin'];
        }

        if ($mots !== null) {
            $erreurs = [...$erreurs, ...self::controlerMots($sousTitres, $mots)];
        }

        return $erreurs;
    }

    /**
     * Chaque sous-titre doit finir à la limite d'un mot source, et le texte doit reprendre
     * tous les mots, dans l'ordre.
     *
     * @param  list<array{numero: int, debut: int, fin: int, lignes: list<string>}>  $sousTitres
     * @param  list<MotTranscrit>  $mots
     * @return list<string>
     */
    private static function controlerMots(array $sousTitres, array $mots): array
    {
        $limites = [];
        $position = 0;
        $motsSansEspace = array_map(fn (MotTranscrit $m) => preg_replace('/\s+/u', '', $m->texte), $mots);

        foreach ($motsSansEspace as $index => $texte) {
            $position += mb_strlen($texte);
            $suivant = $motsSansEspace[$index + 1] ?? null;

            // Une ponctuation seule est rattachée au mot précédent : pas de coupure entre les deux.
            if ($suivant === null || preg_match(self::PONCTUATION_SEULE, $suivant) !== 1) {
                $limites[$position] = true;
            }
        }

        $total = $position;
        $texteSource = implode('', $motsSansEspace);
        $position = 0;
        $erreurs = [];

        foreach ($sousTitres as $st) {
            $texte = preg_replace('/\s+/u', '', implode('', $st['lignes']));
            $position += mb_strlen($texte);

            if (! isset($limites[$position]) && $position !== $total) {
                $erreurs[] = "Sous-titre {$st['numero']} : coupure au milieu d’un mot (position {$position}).";
            }
        }

        $texteSousTitres = preg_replace('/\s+/u', '', implode('', array_map(
            fn (array $st) => implode('', $st['lignes']),
            $sousTitres,
        )));

        if ($texteSousTitres !== $texteSource) {
            $erreurs[] = 'Le texte des sous-titres ne reprend pas tous les mots source, dans l’ordre.';
        }

        return $erreurs;
    }

    /** @return list<string> Blocs séparés par une ligne vide, sans les lignes vides en bordure. */
    private static function blocs(string $contenu): array
    {
        $contenu = trim($contenu, "\n");

        return $contenu === '' ? [] : preg_split('/\n\n+/', $contenu);
    }

    private static function enMs(int $heures, int $minutes, int $secondes, int $millisecondes): int
    {
        return (($heures * 60 + $minutes) * 60 + $secondes) * 1000 + $millisecondes;
    }
}
