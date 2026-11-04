<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook reçu du prestataire. « event_id » est unique : un même événement n'est enregistré et
 * traité qu'une fois. « traite_at » est renseigné quand le traitement a abouti.
 */
#[Fillable(['event_id', 'type', 'user_id', 'charge', 'recu_at', 'traite_at', 'tentatives', 'erreur'])]
class EvenementPaiement extends Model
{
    protected $table = 'evenements_paiement';

    protected function casts(): array
    {
        return [
            'charge' => 'array',
            'recu_at' => 'datetime',
            'traite_at' => 'datetime',
            'tentatives' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
