<?php

namespace App\Contracts;

use App\Data\Ia\ReponseModele;
use App\Data\Ia\RequeteModele;
use App\Exceptions\ErreurModele;

/**
 * Accès interne à un modèle de langage (V2). Le fournisseur reste derrière cette interface.
 *
 * Les instructions (RequeteModele::$instructions) et les données (RequeteModele::$donnees, par
 * exemple le texte d'une transcription) sont envoyées dans des parties séparées. Les données sont
 * présentées comme des contenus à traiter, jamais comme des instructions.
 */
interface LanguageModel
{
    /**
     * Texte libre.
     *
     * @throws ErreurModele
     */
    public function generer(RequeteModele $requete): ReponseModele;

    /**
     * Sortie structurée, validée contre le schéma JSON donné. Une sortie invalide est relancée une
     * fois (voxia.ia.modele.relances_sortie_invalide), puis l'opération échoue.
     *
     * @param  array<string, mixed>  $schema
     *
     * @throws ErreurModele
     */
    public function genererStructure(RequeteModele $requete, array $schema): ReponseModele;
}
