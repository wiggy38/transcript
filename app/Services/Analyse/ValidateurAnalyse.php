<?php

namespace App\Services\Analyse;

use App\Data\MotTranscrit;
use App\Data\PartieAnalysee;
use App\Enums\TypePartieScript;
use App\Exceptions\ErreurAnalyse;
use App\Support\TexteMots;

/**
 * Contrôle côté serveur de la réponse d'un fournisseur (F7). Fait foi, quel que soit le fournisseur.
 *
 * - Les cinq types sont présents une fois chacun.
 * - Chaque borne est recalée sur le mot réel le plus proche ; un écart supérieur à
 *   voxia.script_analysis.recalage_max_ms rend la réponse invalide.
 * - Les parties suivent l'ordre de la vidéo et ne se chevauchent pas (chaque début de partie
 *   tombe après la fin de la précédente, au niveau des mots).
 * - Le texte est reconstruit à partir des mots de la plage : le texte renvoyé par le fournisseur
 *   est ignoré. Une partie absente reste absente.
 */
final class ValidateurAnalyse
{
    /**
     * @param  list<PartieAnalysee>  $parties
     * @param  list<MotTranscrit>  $mots
     * @return list<PartieAnalysee> les cinq parties, dans l'ordre des types
     *
     * @throws ErreurAnalyse
     */
    public function valider(array $parties, array $mots): array
    {
        if ($mots === []) {
            throw new ErreurAnalyse('aucun mot à analyser');
        }

        $parType = [];

        foreach ($parties as $partie) {
            if (isset($parType[$partie->type->value])) {
                throw new ErreurAnalyse("partie en double : {$partie->type->value}");
            }

            $parType[$partie->type->value] = $partie;
        }

        $tolerance = (int) config('voxia.script_analysis.recalage_max_ms');
        $sorties = [];
        $finPrecedente = -1;

        foreach (TypePartieScript::cases() as $type) {
            $partie = $parType[$type->value] ?? throw new ErreurAnalyse("partie absente de la réponse : {$type->value}");

            if (! $partie->detectee) {
                $sorties[] = new PartieAnalysee($type, false);

                continue;
            }

            if ($partie->debutMs === null || $partie->finMs === null || $partie->debutMs > $partie->finMs) {
                throw new ErreurAnalyse("bornes incohérentes : {$type->value}");
            }

            [$indexDebut, $ecartDebut] = $this->motLePlusProche($mots, $partie->debutMs, fn (MotTranscrit $mot) => $mot->debutMs);
            [$indexFin, $ecartFin] = $this->motLePlusProche($mots, $partie->finMs, fn (MotTranscrit $mot) => $mot->finMs);

            if ($ecartDebut > $tolerance || $ecartFin > $tolerance) {
                throw new ErreurAnalyse("borne hors du texte transcrit : {$type->value}");
            }

            if ($indexDebut > $indexFin) {
                throw new ErreurAnalyse("partie à l'envers : {$type->value}");
            }

            if ($indexDebut <= $finPrecedente) {
                throw new ErreurAnalyse("chevauchement ou désordre : {$type->value}");
            }

            $finPrecedente = $indexFin;
            $plage = array_slice($mots, $indexDebut, $indexFin - $indexDebut + 1);

            $sorties[] = new PartieAnalysee(
                type: $type,
                detectee: true,
                debutMs: $mots[$indexDebut]->debutMs,
                finMs: $mots[$indexFin]->finMs,
                texte: TexteMots::joindre($plage),
            );
        }

        return $sorties;
    }

    /**
     * Indice du mot dont la borne est la plus proche de « ms », et l'écart. À égalité, le premier mot.
     *
     * @param  list<MotTranscrit>  $mots
     * @return array{0: int, 1: int}
     */
    private function motLePlusProche(array $mots, int $ms, callable $borne): array
    {
        $meilleur = 0;
        $ecart = PHP_INT_MAX;

        foreach ($mots as $index => $mot) {
            $distance = abs($borne($mot) - $ms);

            if ($distance < $ecart) {
                $meilleur = $index;
                $ecart = $distance;
            }
        }

        return [$meilleur, $ecart];
    }
}
