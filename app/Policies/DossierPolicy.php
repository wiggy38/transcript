<?php

namespace App\Policies;

use App\Models\Dossier;
use App\Models\User;
use App\Services\Plans\PlanFeatures;

/**
 * Dossiers de la bibliothèque (V2). Créer, renommer et supprimer demande le plan (Pro, Business) ;
 * un compte qui n'a plus ce droit garde ses dossiers en lecture seule. Jamais d'accès au dossier d'un autre compte.
 */
class DossierPolicy
{
    public function create(User $utilisateur): bool
    {
        return app(PlanFeatures::class)->peutClasser($utilisateur);
    }

    public function update(User $utilisateur, Dossier $dossier): bool
    {
        return $dossier->user_id === $utilisateur->getKey()
            && app(PlanFeatures::class)->peutClasser($utilisateur);
    }

    public function delete(User $utilisateur, Dossier $dossier): bool
    {
        return $this->update($utilisateur, $dossier);
    }
}
