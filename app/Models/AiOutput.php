<?php

namespace App\Models;

use App\Enums\StatutSortieIa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sortie générée par le modèle de langage pour une transcription (résumé, V2). */
#[Fillable([
    'transcription_id',
    'fonctionnalite',
    'format',
    'statut',
    'langue',
    'parametres',
    'contenu',
    'texte_recherche',
    'config_recherche',
    'credits',
    'fournisseur',
    'modele',
    'cout_estime_usd',
    'date_debut',
    'date_termine',
    'date_echec',
])]
class AiOutput extends Model
{
    protected function casts(): array
    {
        return [
            'statut' => StatutSortieIa::class,
            'contenu' => 'array',
            'parametres' => 'array',
            'credits' => 'integer',
            'cout_estime_usd' => 'decimal:4',
            'date_debut' => 'datetime',
            'date_termine' => 'datetime',
            'date_echec' => 'datetime',
        ];
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }
}
