<?php

namespace App\Console\Commands;

use App\Contracts\ScriptAnalyzer;
use App\Data\EntreeAnalyse;
use App\Data\MotTranscrit;
use App\Data\PartieAnalysee;
use App\Enums\TypePartieScript;
use App\Exceptions\ErreurAnalyse;
use App\Services\Analyse\ValidateurAnalyse;
use App\Services\Transcription\ParagraphBuilder;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Évalue l'analyse de script (cahier F7) sur des pubs découpées à la main.
 *
 * Une limite est une borne de partie : une partie présente compte deux limites (début et fin), une
 * partie absente en compte une (juste si l'analyse la déclare absente). Une limite est juste si elle
 * tombe à voxia.script_analysis.eval.tolerance_ms près de la référence. Une pub en échec d'analyse
 * compte toutes ses limites comme fausses. Code de sortie 1 sous la cible (80 % par défaut).
 */
class EvaluerScripts extends Command
{
    protected $signature = 'voxia:eval-scripts {--fichier= : Fichier de référence (par défaut tests/fixtures/pubs-reference.json)}';

    protected $description = 'Compare les analyses de script aux découpages de référence (limites à 2 s près)';

    public function handle(ScriptAnalyzer $analyseur, ValidateurAnalyse $validateur, ParagraphBuilder $paragraphes): int
    {
        $chemin = (string) ($this->option('fichier') ?: base_path('tests/fixtures/pubs-reference.json'));

        try {
            $pubs = $this->lirePubs($chemin);
        } catch (InvalidArgumentException $erreur) {
            $this->error($erreur->getMessage());

            return self::FAILURE;
        }

        $tolerance = (int) config('voxia.script_analysis.eval.tolerance_ms');
        $delaiMax = (int) config('voxia.script_analysis.eval.delai_max_secondes');
        $cible = (float) config('voxia.script_analysis.eval.cible');

        $justes = 0;
        $total = 0;
        $lignes = [];
        $tropLentes = 0;

        foreach ($pubs as $pub) {
            $mots = array_map(
                fn (array $mot) => new MotTranscrit((string) $mot['texte'], (int) $mot['debut_ms'], (int) $mot['fin_ms']),
                $pub['mots'],
            );

            $entree = new EntreeAnalyse(
                paragraphes: $paragraphes->construire($mots),
                mots: $mots,
                dureeMs: (int) $pub['duree_ms'],
            );

            $debut = hrtime(true);
            $obtenues = [];
            $erreur = '';

            try {
                $resultat = $analyseur->analyser($entree);
                $obtenues = $this->parTypes($validateur->valider($resultat->parties, $mots));
            } catch (ErreurAnalyse $exception) {
                $erreur = $exception->getMessage();
            }

            $duree = (hrtime(true) - $debut) / 1_000_000_000;

            if ($duree > $delaiMax) {
                $tropLentes++;
            }

            [$justesPub, $totalPub] = $this->compter($pub['parties'], $obtenues, $tolerance);
            $justes += $justesPub;
            $total += $totalPub;

            $lignes[] = [
                $pub['id'],
                "{$justesPub} / {$totalPub}",
                number_format($duree, 1).' s',
                $erreur,
            ];
        }

        $this->table(['Pub', 'Limites justes', 'Délai', 'Erreur'], $lignes);

        $taux = $total > 0 ? $justes / $total : 0.0;

        $this->line(sprintf('Limites justes : %d / %d (%.1f %%), cible %.0f %%.', $justes, $total, $taux * 100, $cible * 100));

        if ($tropLentes > 0) {
            $this->warn("{$tropLentes} pub(s) au-delà de {$delaiMax} s.");
        }

        return $taux >= $cible ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<array{id: string, duree_ms: int, mots: list<array<string, mixed>>, parties: array<string, array{debut_ms: int, fin_ms: int}|null>}>
     */
    private function lirePubs(string $chemin): array
    {
        if (! is_file($chemin)) {
            throw new InvalidArgumentException("Fichier de référence introuvable : {$chemin}");
        }

        $donnees = json_decode((string) file_get_contents($chemin), true);

        if (! is_array($donnees) || ! is_array($donnees['pubs'] ?? null) || $donnees['pubs'] === []) {
            throw new InvalidArgumentException('Fichier de référence invalide : une liste « pubs » non vide est attendue.');
        }

        $types = array_map(fn (TypePartieScript $type) => $type->value, TypePartieScript::cases());

        foreach ($donnees['pubs'] as $pub) {
            $id = (string) ($pub['id'] ?? '?');

            if (! is_array($pub['mots'] ?? null) || ! isset($pub['duree_ms']) || ! is_array($pub['parties'] ?? null)) {
                throw new InvalidArgumentException("Pub « {$id} » : champs « id », « duree_ms », « mots » et « parties » requis.");
            }

            foreach ($types as $type) {
                if (! array_key_exists($type, $pub['parties'])) {
                    throw new InvalidArgumentException("Pub « {$id} » : la partie « {$type} » doit figurer (valeur null si absente).");
                }
            }
        }

        return $donnees['pubs'];
    }

    /**
     * @param  list<PartieAnalysee>  $parties
     * @return array<string, PartieAnalysee>
     */
    private function parTypes(array $parties): array
    {
        $resultat = [];

        foreach ($parties as $partie) {
            $resultat[$partie->type->value] = $partie;
        }

        return $resultat;
    }

    /**
     * @param  array<string, array{debut_ms: int, fin_ms: int}|null>  $reference
     * @param  array<string, PartieAnalysee>  $obtenues
     * @return array{0: int, 1: int} limites justes et limites totales
     */
    private function compter(array $reference, array $obtenues, int $tolerance): array
    {
        $justes = 0;
        $total = 0;

        foreach (TypePartieScript::cases() as $type) {
            $attendue = $reference[$type->value];
            $partie = $obtenues[$type->value] ?? null;
            $detectee = $partie !== null && $partie->detectee;

            if ($attendue === null) {
                $total++;

                if (! $detectee) {
                    $justes++;
                }

                continue;
            }

            $total += 2;

            if ($detectee) {
                $justes += abs($partie->debutMs - (int) $attendue['debut_ms']) <= $tolerance ? 1 : 0;
                $justes += abs($partie->finMs - (int) $attendue['fin_ms']) <= $tolerance ? 1 : 0;
            }
        }

        return [$justes, $total];
    }
}
