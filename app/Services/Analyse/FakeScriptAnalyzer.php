<?php

namespace App\Services\Analyse;

use App\Contracts\ScriptAnalyzer;
use App\Data\EntreeAnalyse;
use App\Data\ResultatFournisseur;
use App\Exceptions\ErreurAnalyse;

/**
 * Fausse implémentation de ScriptAnalyzer pour les tests (aucun appel réseau). Les réponses se
 * mettent en file, dans l'ordre des appels. Sans réponse en file, l'analyse échoue. Chaque appel
 * est noté dans $appels pour les assertions.
 */
final class FakeScriptAnalyzer implements ScriptAnalyzer
{
    /** @var list<ResultatFournisseur|ErreurAnalyse> */
    private array $file = [];

    /** @var list<EntreeAnalyse> */
    public array $appels = [];

    public function repondre(ResultatFournisseur $reponse): self
    {
        $this->file[] = $reponse;

        return $this;
    }

    public function echouer(ErreurAnalyse $erreur): self
    {
        $this->file[] = $erreur;

        return $this;
    }

    public function analyser(EntreeAnalyse $entree): ResultatFournisseur
    {
        $this->appels[] = $entree;

        $suivant = array_shift($this->file);

        if ($suivant instanceof ErreurAnalyse) {
            throw $suivant;
        }

        return $suivant ?? throw new ErreurAnalyse('aucune réponse en file');
    }
}
