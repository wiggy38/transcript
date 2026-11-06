<?php

namespace App\Models;

use App\Enums\TypeEtiquette;
use Database\Factories\EtiquetteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Étiquette de la bibliothèque (V2) : niche, produit, concurrent ou libre. */
#[Fillable(['user_id', 'type', 'nom', 'nom_normalise'])]
class Etiquette extends Model
{
    /** @use HasFactory<EtiquetteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TypeEtiquette::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcriptions(): BelongsToMany
    {
        return $this->belongsToMany(Transcription::class, 'etiquette_transcription');
    }
}
