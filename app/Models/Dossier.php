<?php

namespace App\Models;

use Database\Factories\DossierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Dossier de la bibliothèque (V2) : un seul niveau, créé par l'utilisateur. */
#[Fillable(['user_id', 'nom', 'nom_normalise'])]
class Dossier extends Model
{
    /** @use HasFactory<DossierFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcriptions(): HasMany
    {
        return $this->hasMany(Transcription::class);
    }
}
