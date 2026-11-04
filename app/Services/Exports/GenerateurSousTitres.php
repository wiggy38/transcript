<?php

namespace App\Services\Exports;

use App\Data\MotTranscrit;
use App\Data\SousTitre;

/**
 * Découpe les mots horodatés au mot en sous-titres (cahier F4) :
 *  - 42 caractères au plus par ligne, 2 lignes au plus (voxia.subtitles) ;
 *  - 1 à 7 secondes par sous-titre ;
 *  - coupure de préférence à la fin de phrase, puis à une virgule, puis entre deux mots ;
 *    jamais au milieu d'un mot ;
 *  - aucun chevauchement : un sous-titre se termine au plus tard quand le suivant commence.
 *
 * Un mot de ponctuation seule (« , » « . ») reste collé au mot précédent.
 *
 * Une unité est un tableau : texte, debut, fin, et deux drapeaux (commence ou finit par un
 * caractère d'écriture sans espace, comme le japonais). Les drapeaux évitent une expression
 * régulière par jointure : l'export d'une transcription de 3 heures doit rester rapide.
 */
final class GenerateurSousTitres
{
    private const FIN_DE_PHRASE = '/[.!?…]["\'»”’)\]]*$/u';

    private const PONCTUATION_FAIBLE = '/[,;:]["\'»”’)\]]*$/u';

    private const PONCTUATION_SEULE = '/^[.,;:!?…)»”"\]]+$/u';

    /** Écritures sans espace entre les mots (une seule expression, appliquée une fois par mot). */
    private const SANS_ESPACE = '/[\p{Han}\p{Hiragana}\p{Katakana}\p{Thai}]/u';

    private int $maxCaracteres = 42;

    private int $maxLignes = 2;

    private int $minMs = 1000;

    private int $maxMs = 7000;

    private float $ratioVirgule = 0.75;

    /**
     * @param  list<MotTranscrit>  $mots  Dans l'ordre de la vidéo.
     * @return list<SousTitre>
     */
    public function construire(array $mots): array
    {
        $this->lireConfiguration();

        $unites = $this->unites($mots);

        if ($unites === []) {
            return [];
        }

        $groupes = $this->fusionnerTropCourts($this->regrouper($unites));

        return $this->horodater($groupes);
    }

    private function lireConfiguration(): void
    {
        $this->maxCaracteres = (int) config('voxia.subtitles.max_chars_per_line');
        $this->maxLignes = (int) config('voxia.subtitles.max_lines');
        $this->minMs = (int) config('voxia.subtitles.min_duration_seconds') * 1000;
        $this->maxMs = (int) config('voxia.subtitles.max_duration_seconds') * 1000;
        $this->ratioVirgule = (float) config('voxia.subtitles.coupure_ponctuation_faible_ratio');
    }

    /**
     * Mots fusionnés à leur ponctuation : une unité est un bloc qui ne sera jamais coupé.
     *
     * @param  list<MotTranscrit>  $mots
     * @return list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>
     */
    private function unites(array $mots): array
    {
        $unites = [];

        foreach ($mots as $mot) {
            $texte = trim($mot->texte);

            if ($texte === '') {
                continue;
            }

            if ($unites !== [] && preg_match(self::PONCTUATION_SEULE, $texte) === 1) {
                $dernier = count($unites) - 1;
                $unites[$dernier]['texte'] .= $texte;
                $unites[$dernier]['fin'] = max($unites[$dernier]['fin'], $mot->finMs);
                $unites[$dernier]['finSansEspace'] = false;

                continue;
            }

            $unites[] = [
                'texte' => $texte,
                'debut' => $mot->debutMs,
                'fin' => $mot->finMs,
                'debutSansEspace' => $this->sansEspace(mb_substr($texte, 0, 1)),
                'finSansEspace' => $this->sansEspace(mb_substr($texte, -1)),
            ];
        }

        return $unites;
    }

    /**
     * @param  list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>  $unites
     * @return list<list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>>
     */
    private function regrouper(array $unites): array
    {
        $groupes = [];
        $courant = [];

        foreach ($unites as $unite) {
            if ($courant === []) {
                $courant = [$unite];

                continue;
            }

            if (! $this->tient([...$courant, $unite])) {
                $groupes[] = $courant;
                $courant = [$unite];

                continue;
            }

            if ($this->dureeMs($courant) >= $this->minMs && $this->coupureConseillee($courant)) {
                $groupes[] = $courant;
                $courant = [$unite];

                continue;
            }

            $courant[] = $unite;
        }

        if ($courant !== []) {
            $groupes[] = $courant;
        }

        return $groupes;
    }

    /**
     * Un groupe trop court (moins d'une seconde) est fusionné avec le suivant, si cela tient.
     *
     * @param  list<list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>>  $groupes
     * @return list<list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>>
     */
    private function fusionnerTropCourts(array $groupes): array
    {
        $resultat = [];
        $nombre = count($groupes);

        for ($i = 0; $i < $nombre; $i++) {
            $groupe = $groupes[$i];

            while ($i + 1 < $nombre
                && $this->dureeMs($groupe) < $this->minMs
                && $this->tient([...$groupe, ...$groupes[$i + 1]])) {
                $groupe = [...$groupe, ...$groupes[$i + 1]];
                $i++;
            }

            $resultat[] = $groupe;
        }

        return $resultat;
    }

    /**
     * Fixe les débuts et fins. Un sous-titre trop court s'étire vers l'avant, sans
     * empiéter sur le suivant.
     *
     * @param  list<list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>>  $groupes
     * @return list<SousTitre>
     */
    private function horodater(array $groupes): array
    {
        $sousTitres = [];
        $nombre = count($groupes);

        for ($i = 0; $i < $nombre; $i++) {
            $groupe = $groupes[$i];
            $debut = $groupe[0]['debut'];
            $fin = $groupe[count($groupe) - 1]['fin'];
            $limite = $i + 1 < $nombre ? $groupes[$i + 1][0]['debut'] : null;

            if ($limite !== null) {
                $fin = min($fin, $limite);
            }

            if ($fin - $debut < $this->minMs) {
                $cible = $debut + $this->minMs;

                if ($limite !== null) {
                    $cible = min($cible, $limite);
                }

                $fin = max($fin, $cible);
            }

            if ($fin <= $debut) {
                $fin = $debut + 1;
            }

            $sousTitres[] = new SousTitre($debut, $fin, $this->lignes($groupe));
        }

        return $sousTitres;
    }

    /**
     * Un groupe tient s'il reste dans le nombre de lignes et dans la durée maximale.
     *
     * @param  list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>  $groupe
     */
    private function tient(array $groupe): bool
    {
        return $this->dureeMs($groupe) <= $this->maxMs
            && count($this->lignes($groupe)) <= $this->maxLignes;
    }

    /**
     * Fin de phrase : coupure sûre. Virgule : coupure seulement si le sous-titre est déjà bien rempli.
     *
     * @param  list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>  $courant
     */
    private function coupureConseillee(array $courant): bool
    {
        $dernier = $courant[count($courant) - 1]['texte'];

        if (preg_match(self::FIN_DE_PHRASE, $dernier) === 1) {
            return true;
        }

        if (preg_match(self::PONCTUATION_FAIBLE, $dernier) === 1) {
            $capacite = $this->maxCaracteres * $this->maxLignes;
            $longueur = 0;

            foreach ($courant as $unite) {
                $longueur += mb_strlen($unite['texte']) + 1;
            }

            return $longueur - 1 >= $this->ratioVirgule * $capacite;
        }

        return false;
    }

    /**
     * Découpe un groupe en lignes de 42 caractères au plus, sans couper une unité.
     *
     * @param  list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>  $groupe
     * @return list<string>
     */
    private function lignes(array $groupe): array
    {
        $lignes = [];
        $ligne = '';
        $ligneFinSansEspace = false;

        foreach ($groupe as $unite) {
            if ($ligne === '') {
                $ligne = $unite['texte'];
                $ligneFinSansEspace = $unite['finSansEspace'];

                continue;
            }

            $separateur = $ligneFinSansEspace && $unite['debutSansEspace'] ? '' : ' ';
            $candidate = $ligne.$separateur.$unite['texte'];

            if (mb_strlen($candidate) <= $this->maxCaracteres) {
                $ligne = $candidate;
                $ligneFinSansEspace = $unite['finSansEspace'];

                continue;
            }

            $lignes[] = $ligne;
            $ligne = $unite['texte'];
            $ligneFinSansEspace = $unite['finSansEspace'];
        }

        if ($ligne !== '') {
            $lignes[] = $ligne;
        }

        return $lignes;
    }

    /** @param  list<array{texte: string, debut: int, fin: int, debutSansEspace: bool, finSansEspace: bool}>  $groupe */
    private function dureeMs(array $groupe): int
    {
        return $groupe[count($groupe) - 1]['fin'] - $groupe[0]['debut'];
    }

    private function sansEspace(string $caractere): bool
    {
        return $caractere !== '' && preg_match(self::SANS_ESPACE, $caractere) === 1;
    }
}
