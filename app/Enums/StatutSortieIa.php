<?php

namespace App\Enums;

/** Cycle de vie d'une sortie générée (résumé). Un échec ne consomme aucun crédit. */
enum StatutSortieIa: string
{
    case EnAttente = 'en_attente';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Echec = 'echec';

    /** Vrai tant que la tâche n'a pas rendu sa réponse : les crédits sont alors réservés. */
    public function enCours(): bool
    {
        return in_array($this, [self::EnAttente, self::EnCours], true);
    }
}
