<?php

namespace App\Models;

use App\Support\ConfigurationRecherche;
use Database\Factories\TranscriptionParagraphFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

#[Fillable(['transcription_id', 'position', 'debut_ms', 'fin_ms', 'texte', 'config_recherche'])]
class TranscriptionParagraph extends Model
{
    /** @use HasFactory<TranscriptionParagraphFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'debut_ms' => 'integer',
            'fin_ms' => 'integer',
        ];
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }

    /**
     * Recherche plein texte PostgreSQL pour une configuration donnée. L'expression et la
     * configuration doivent être celles de l'index partiel de cette langue (migration
     * ajouter_recherche_par_langue) pour que l'index serve la requête. La configuration
     * n'est écrite dans le SQL qu'après vérification dans la liste fermée.
     */
    public function scopeRecherche(Builder $query, string $terme, string $configuration = 'simple'): void
    {
        if (! in_array($configuration, ConfigurationRecherche::toutes(), true)) {
            throw new InvalidArgumentException("Configuration de recherche inconnue : {$configuration}");
        }

        $query->whereRaw("transcription_paragraphs.config_recherche = '{$configuration}'")
            ->whereRaw(
                "to_tsvector('{$configuration}', transcription_paragraphs.texte) @@ plainto_tsquery('{$configuration}', ?)",
                [$terme]
            );
    }
}
