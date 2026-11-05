<?php

namespace App\Data\Resume;

/** Contenu validé d'un résumé : texte, points clés et chapitres (ces derniers seulement pour une vidéo longue). */
final readonly class ContenuResume
{
    /**
     * @param  list<PointResume>  $points
     * @param  list<ChapitreResume>  $chapitres
     */
    public function __construct(
        public string $resume,
        public array $points,
        public array $chapitres,
    ) {}

    /**
     * Texte indexé par la recherche : résumé, points clés et chapitres (titres et résumés), un bloc par ligne.
     * La structure (clés, horodatages) n'y figure pas.
     */
    public function texteRecherche(): string
    {
        $blocs = [$this->resume, ...array_map(fn (PointResume $p) => $p->texte, $this->points)];

        foreach ($this->chapitres as $chapitre) {
            $blocs[] = $chapitre->titre;
            $blocs[] = $chapitre->resume;
        }

        return implode("\n", $blocs);
    }

    /**
     * Forme stockée dans ai_outputs.contenu.
     *
     * @return array{resume: string, points: list<array{texte: string, paragraphe: int, debut_ms: int}>, chapitres: list<array{titre: string, paragraphe: int, debut_ms: int, resume: string}>}
     */
    public function enTableau(): array
    {
        return [
            'resume' => $this->resume,
            'points' => array_map(fn (PointResume $p) => [
                'texte' => $p->texte,
                'paragraphe' => $p->paragraphe,
                'debut_ms' => $p->debutMs,
            ], $this->points),
            'chapitres' => array_map(fn (ChapitreResume $c) => [
                'titre' => $c->titre,
                'paragraphe' => $c->paragraphe,
                'debut_ms' => $c->debutMs,
                'resume' => $c->resume,
            ], $this->chapitres),
        ];
    }
}
