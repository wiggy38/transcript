<?php

namespace App\Policies;

use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;

/**
 * Transcriptions : seul le classement dans la bibliothèque est ici (V2). Les autres accès restent
 * contrôlés dans les contrôleurs (abort_unless sur user_id), jusqu'à leur migration vers des Policies.
 */
class TranscriptionPolicy
{
    /** Classer une transcription (dossier, étiquettes) : propriétaire et plan Pro ou Business. */
    public function classer(User $utilisateur, Transcription $transcription): bool
    {
        return $transcription->user_id === $utilisateur->getKey()
            && app(PlanFeatures::class)->peutClasser($utilisateur);
    }
}
