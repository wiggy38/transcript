<?php

namespace App\Policies;

use App\Models\Etiquette;
use App\Models\User;
use App\Services\Plans\PlanFeatures;

/**
 * Étiquettes de la bibliothèque (V2). Créer, renommer et supprimer demande le plan (Pro, Business) ;
 * un compte qui n'a plus ce droit garde ses étiquettes en lecture seule. Jamais d'accès à l'étiquette d'un autre compte.
 */
class EtiquettePolicy
{
    public function create(User $utilisateur): bool
    {
        return app(PlanFeatures::class)->peutClasser($utilisateur);
    }

    public function update(User $utilisateur, Etiquette $etiquette): bool
    {
        return $etiquette->user_id === $utilisateur->getKey()
            && app(PlanFeatures::class)->peutClasser($utilisateur);
    }

    public function delete(User $utilisateur, Etiquette $etiquette): bool
    {
        return $this->update($utilisateur, $etiquette);
    }
}
