<?php

namespace App\Services\Resume;

use App\Data\Resume\ChapitreResume;
use App\Data\Resume\ContenuResume;
use App\Data\Resume\ExigencesResume;
use App\Data\Resume\ParagrapheResume;
use App\Data\Resume\PointResume;
use App\Enums\ModeChapitres;
use App\Exceptions\ErreurResume;

/**
 * Vérifie la sortie du modèle contre les règles du résumé (cahier V2, fidélité) et construit le contenu.
 *
 * - Un horodatage n'est jamais repris du modèle : un point ou un chapitre cite un numéro de paragraphe,
 *   et son début est lu dans la transcription. Un numéro inconnu, ou au-delà de la durée, fait retirer le point.
 * - Les nombres d'une sortie doivent figurer dans la transcription : c'est le contrôle minimal contre les
 *   chiffres ajoutés. Il ne couvre ni les noms propres ni les affirmations (limite connue, voir avancement).
 * - Une règle non respectée lève ErreurResume : le service relance, puis échoue sans décompte.
 */
final class ValidateurResume
{
    /**
     * @param  array<string, mixed>  $structure  sortie décodée, déjà conforme au schéma
     * @param  array<int, ParagrapheResume>  $paragraphes  paragraphes admis, indexés par numéro
     * @param  string  $texteComplet  toute la transcription, pour le contrôle des nombres
     *
     * @throws ErreurResume
     */
    public function valider(array $structure, array $paragraphes, ExigencesResume $exigences, string $texteComplet, int $dureeSecondes): ContenuResume
    {
        $dureeMs = $dureeSecondes * 1000;
        $resume = $this->texte($structure['resume'] ?? null);

        if (! $this->dansBornes($this->phrases($resume), (int) config('voxia.ia.resume.phrases_resume.min'), (int) config('voxia.ia.resume.phrases_resume.max'))) {
            throw new ErreurResume('phrases_resume');
        }

        $points = $this->points($structure, $paragraphes, $dureeMs, $exigences);
        $chapitres = $this->chapitres($structure, $paragraphes, $dureeMs, $exigences);

        $this->verifierNombres([$resume, ...array_map(fn (PointResume $p) => $p->texte, $points), ...$this->textesChapitres($chapitres)], $texteComplet);

        return new ContenuResume($resume, $points, $chapitres);
    }

    /**
     * Nombre de phrases : découpe après un point, un point d'interrogation, un point d'exclamation ou des points de suspension.
     */
    public function phrases(string $texte): int
    {
        $morceaux = preg_split('/(?<=[.!?…])\s+/u', trim($texte), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($morceaux) ? count($morceaux) : 0;
    }

    /**
     * @param  array<string, mixed>  $structure
     * @param  array<int, ParagrapheResume>  $paragraphes
     * @return list<PointResume>
     */
    private function points(array $structure, array $paragraphes, int $dureeMs, ExigencesResume $exigences): array
    {
        $points = [];

        foreach ((array) ($structure['points_cles'] ?? []) as $brut) {
            $texte = $this->texte(is_array($brut) ? ($brut['texte'] ?? null) : null);
            $numero = is_array($brut) ? ($brut['paragraphe'] ?? null) : null;

            if ($texte === '' || ! is_int($numero)) {
                throw new ErreurResume('point_invalide');
            }

            $paragraphe = $this->paragrapheAdmis($numero, $paragraphes, $dureeMs);

            if ($paragraphe === null) {
                continue; // Numéro inconnu ou hors durée : le point est retiré, son horodatage n'existe nulle part.
            }

            $points[] = new PointResume($texte, $paragraphe->numero, $paragraphe->debutMs);
        }

        if (count($points) < $exigences->pointsMin) {
            throw new ErreurResume('points');
        }

        usort($points, fn (PointResume $a, PointResume $b) => $a->debutMs <=> $b->debutMs);

        return array_slice($points, 0, $exigences->pointsMax);
    }

    /**
     * @param  array<string, mixed>  $structure
     * @param  array<int, ParagrapheResume>  $paragraphes
     * @return list<ChapitreResume>
     */
    private function chapitres(array $structure, array $paragraphes, int $dureeMs, ExigencesResume $exigences): array
    {
        if ($exigences->chapitres === ModeChapitres::Aucun) {
            return []; // Vidéo courte : la sortie n'a pas de chapitres, même si le modèle en a proposé.
        }

        $chapitres = [];

        foreach ((array) ($structure['chapitres'] ?? []) as $brut) {
            $titre = $this->texte(is_array($brut) ? ($brut['titre'] ?? null) : null);
            $resume = $this->texte(is_array($brut) ? ($brut['resume'] ?? null) : null);
            $numero = is_array($brut) ? ($brut['paragraphe'] ?? null) : null;

            if ($titre === '' || ! is_int($numero)) {
                throw new ErreurResume('chapitre_invalide');
            }

            if (! $this->dansBornes($this->phrases($resume), (int) config('voxia.ia.resume.phrases_chapitre'), (int) config('voxia.ia.resume.phrases_chapitre'))) {
                throw new ErreurResume('phrases_chapitre');
            }

            $paragraphe = $this->paragrapheAdmis($numero, $paragraphes, $dureeMs);

            if ($paragraphe === null) {
                continue;
            }

            $chapitres[] = new ChapitreResume($titre, $paragraphe->numero, $paragraphe->debutMs, $resume);
        }

        if ($exigences->chapitres === ModeChapitres::Requis && $chapitres === []) {
            throw new ErreurResume('chapitres');
        }

        usort($chapitres, fn (ChapitreResume $a, ChapitreResume $b) => $a->debutMs <=> $b->debutMs);

        return $chapitres;
    }

    /** @param  array<int, ParagrapheResume>  $paragraphes */
    private function paragrapheAdmis(int $numero, array $paragraphes, int $dureeMs): ?ParagrapheResume
    {
        $paragraphe = $paragraphes[$numero] ?? null;

        if ($paragraphe === null || $paragraphe->debutMs > $dureeMs) {
            return null;
        }

        return $paragraphe;
    }

    /**
     * @param  list<string>  $textes
     *
     * @throws ErreurResume
     */
    private function verifierNombres(array $textes, string $texteComplet): void
    {
        foreach ($textes as $texte) {
            preg_match_all('/\d+/u', $texte, $trouves);

            foreach ($trouves[0] ?? [] as $nombre) {
                if (preg_match('/(?<!\d)'.preg_quote($nombre, '/').'(?!\d)/u', $texteComplet) !== 1) {
                    throw new ErreurResume('chiffre_absent');
                }
            }
        }
    }

    /**
     * @param  list<ChapitreResume>  $chapitres
     * @return list<string>
     */
    private function textesChapitres(array $chapitres): array
    {
        return array_merge(...array_map(fn (ChapitreResume $c) => [$c->titre, $c->resume], $chapitres) ?: [[]]);
    }

    private function texte(mixed $valeur): string
    {
        return is_string($valeur) ? trim($valeur) : '';
    }

    private function dansBornes(int $valeur, int $min, int $max): bool
    {
        return $valeur >= $min && $valeur <= $max;
    }
}
