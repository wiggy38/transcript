<?php

namespace App\Contracts;

use App\Data\EntreeAnalyse;
use App\Data\ResultatFournisseur;
use App\Exceptions\ErreurAnalyse;

/**
 * Analyse de la structure d'un script publicitaire (F7) : cinq parties dans l'ordre de la vidéo.
 * Le fournisseur (aujourd'hui l'API Claude) reste derrière cette interface ; le résultat est neutre.
 * Les bornes renvoyées sont recalées et le texte reconstruit par App\Services\Analyse\ValidateurAnalyse :
 * l'implémentation n'a pas à être exacte au mot près.
 */
interface ScriptAnalyzer
{
    /**
     * @throws ErreurAnalyse réponse ou appel en échec (le coût déjà engagé est dans l'exception)
     */
    public function analyser(EntreeAnalyse $entree): ResultatFournisseur;
}
