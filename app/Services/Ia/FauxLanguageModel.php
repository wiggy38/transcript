<?php

namespace App\Services\Ia;

use App\Contracts\LanguageModel;
use App\Data\Ia\ReponseModele;
use App\Data\Ia\RequeteModele;
use App\Exceptions\ErreurModele;
use RuntimeException;

/**
 * Faux modèle de langage pour les tests : renvoie les réponses préparées, dans l'ordre, sans appel réseau.
 * Chaque requête reçue est gardée dans « requetes » pour vérifier ce qui a été envoyé.
 */
final class FauxLanguageModel implements LanguageModel
{
    /** @var list<RequeteModele> */
    public array $requetes = [];

    /** @var list<ReponseModele|ErreurModele> */
    private array $file = [];

    public function repondre(ReponseModele|ErreurModele $reponse): self
    {
        $this->file[] = $reponse;

        return $this;
    }

    public function generer(RequeteModele $requete): ReponseModele
    {
        return $this->suivante($requete);
    }

    public function genererStructure(RequeteModele $requete, array $schema): ReponseModele
    {
        return $this->suivante($requete);
    }

    private function suivante(RequeteModele $requete): ReponseModele
    {
        $this->requetes[] = $requete;

        if ($this->file === []) {
            throw new RuntimeException('FauxLanguageModel : aucune réponse préparée.');
        }

        $reponse = array_shift($this->file);

        if ($reponse instanceof ErreurModele) {
            throw $reponse;
        }

        return $reponse;
    }
}
