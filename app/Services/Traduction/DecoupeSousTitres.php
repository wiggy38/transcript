<?php

namespace App\Services\Traduction;

use App\Data\SousTitre;

/**
 * Découpe d'un texte traduit en sous-titres, selon les règles de F4 (voxia.subtitles) :
 *  - 42 caractères au plus par ligne, 2 lignes au plus par sous-titre ;
 *  - 1 seconde au moins par sous-titre ;
 *  - jamais au milieu d'un mot ;
 *  - un segment traduit reste dans les bornes de temps du segment d'origine.
 *
 * Un texte trop long pour sa durée est découpé en plusieurs sous-titres qui se partagent la durée
 * d'origine, au prorata des caractères. Le texte n'est jamais coupé ni tronqué : si la durée ne permet
 * pas le respect de la règle de 1 seconde, tropLong() le signale et l'interface l'affiche.
 */
final class DecoupeSousTitres
{
    /** @return list<string> lignes de 42 caractères au plus, coupées entre deux mots */
    public function lignes(string $texte): array
    {
        $max = (int) config('voxia.subtitles.max_chars_per_line');
        $mots = preg_split('/\s+/u', trim($texte), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lignes = [];
        $courante = '';

        foreach ($mots as $mot) {
            if ($courante === '') {
                $courante = $mot;

                continue;
            }

            if (mb_strlen($courante.' '.$mot) <= $max) {
                $courante .= ' '.$mot;

                continue;
            }

            $lignes[] = $courante;
            $courante = $mot;
        }

        if ($courante !== '') {
            $lignes[] = $courante;
        }

        return $lignes;
    }

    /**
     * Sous-titres d'un segment : groupes de deux lignes, durée partagée au prorata des caractères.
     *
     * @return list<SousTitre>
     */
    public function sousTitres(int $debutMs, int $finMs, string $texte): array
    {
        $parLigne = max(1, (int) config('voxia.subtitles.max_lines'));
        $groupes = array_chunk($this->lignes($texte), $parLigne);

        if ($groupes === []) {
            return [];
        }

        if (count($groupes) === 1) {
            return [new SousTitre($debutMs, $finMs, $groupes[0])];
        }

        $poids = array_map(fn (array $groupe) => max(1, mb_strlen(implode(' ', $groupe))), $groupes);
        $total = array_sum($poids);
        $duree = max(0, $finMs - $debutMs);
        $cumul = 0;
        $sousTitres = [];

        foreach ($groupes as $index => $groupe) {
            $debut = $debutMs + (int) round($duree * $cumul / $total);
            $cumul += $poids[$index];
            $fin = $index === array_key_last($groupes) ? $finMs : $debutMs + (int) round($duree * $cumul / $total);
            $sousTitres[] = new SousTitre($debut, $fin, $groupe);
        }

        return $sousTitres;
    }

    /** Vrai si le texte ne tient pas dans le segment : un mot trop long, ou une durée trop courte pour le texte. */
    public function tropLong(string $texte, int $debutMs, int $finMs): bool
    {
        $max = (int) config('voxia.subtitles.max_chars_per_line');

        foreach ($this->lignes($texte) as $ligne) {
            if (mb_strlen($ligne) > $max) {
                return true;
            }
        }

        return count(array_chunk($this->lignes($texte), $this->lignesParSousTitre())) > $this->sousTitresMax($debutMs, $finMs);
    }

    /** Nombre de caractères qu'un segment peut contenir dans sa durée : la cible donnée au modèle. */
    public function capacite(int $debutMs, int $finMs): int
    {
        return $this->sousTitresMax($debutMs, $finMs) * $this->lignesParSousTitre() * (int) config('voxia.subtitles.max_chars_per_line');
    }

    private function sousTitresMax(int $debutMs, int $finMs): int
    {
        $minMs = (int) config('voxia.subtitles.min_duration_seconds') * 1000;

        return max(1, intdiv(max(0, $finMs - $debutMs), max(1, $minMs)));
    }

    private function lignesParSousTitre(): int
    {
        return max(1, (int) config('voxia.subtitles.max_lines'));
    }
}
