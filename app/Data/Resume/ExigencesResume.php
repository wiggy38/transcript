<?php

namespace App\Data\Resume;

use App\Enums\ModeChapitres;

/**
 * Règles qu'une sortie doit respecter : nombre de points, présence des chapitres. Les règles de phrases
 * et de chiffres sont communes (voxia.ia.resume).
 */
final readonly class ExigencesResume
{
    public function __construct(
        public int $pointsMin,
        public int $pointsMax,
        public ModeChapitres $chapitres,
    ) {}

    /** Sortie finale : 5 à 10 points ; chapitres requis au-delà du seuil (plus de 20 minutes). */
    public static function finales(int $dureeSecondes): self
    {
        return new self(
            (int) config('voxia.ia.resume.points.min'),
            (int) config('voxia.ia.resume.points.max'),
            self::modeChapitres($dureeSecondes, requis: true),
        );
    }

    /** Morceau d'une vidéo longue : quelques points ; chapitre facultatif, la synthèse décide. */
    public static function morceau(int $dureeSecondes): self
    {
        return new self(
            (int) config('voxia.ia.resume.points_par_morceau.min'),
            (int) config('voxia.ia.resume.points_par_morceau.max'),
            self::modeChapitres($dureeSecondes, requis: false),
        );
    }

    private static function modeChapitres(int $dureeSecondes, bool $requis): ModeChapitres
    {
        if ($dureeSecondes <= (int) config('voxia.ia.resume.seuil_chapitres_secondes')) {
            return ModeChapitres::Aucun;
        }

        return $requis ? ModeChapitres::Requis : ModeChapitres::Facultatifs;
    }
}
