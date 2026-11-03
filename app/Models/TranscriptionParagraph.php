<?php

namespace App\Models;

use Database\Factories\TranscriptionParagraphFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transcription_id', 'position', 'debut_ms', 'fin_ms', 'texte'])]
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
     * Recherche plein texte PostgreSQL. L'expression doit être identique à
     * celle de l'index GIN (migration create_transcription_paragraphs) pour
     * que l'index serve la requête.
     */
    public function scopeRecherche(Builder $query, string $terme): void
    {
        $query->whereRaw(
            "to_tsvector('simple', transcription_paragraphs.texte) @@ plainto_tsquery('simple', ?)",
            [$terme]
        );
    }
}
