<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Une ligne par appel au modèle de langage (tentative réussie ou non). Jamais modifiée. */
#[Fillable([
    'user_id',
    'ai_output_id',
    'fonctionnalite',
    'fournisseur',
    'modele',
    'tentative',
    'tokens_entree',
    'tokens_sortie',
    'duree_ms',
    'cout_estime_usd',
    'statut',
])]
class AppelIa extends Model
{
    protected $table = 'appels_ia';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'cout_estime_usd' => 'float',
            'created_at' => 'datetime',
        ];
    }
}
