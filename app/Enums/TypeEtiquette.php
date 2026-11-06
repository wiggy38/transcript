<?php

namespace App\Enums;

/** Types d'étiquettes de la bibliothèque (V2) : libellés dans lang/fr et lang/en (bibliotheque.php). */
enum TypeEtiquette: string
{
    case Niche = 'niche';
    case Produit = 'produit';
    case Concurrent = 'concurrent';
    case Libre = 'libre';

    public function libelle(): string
    {
        return __('bibliotheque.types.'.$this->value);
    }
}
