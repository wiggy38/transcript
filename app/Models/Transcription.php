<?php

namespace App\Models;

use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use Database\Factories\TranscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'source',
    'url_origine',
    'id_video',
    'titre',
    'chaine',
    'miniature_url',
    'duree_secondes',
    'langue_forcee',
    'langue_detectee',
    'statut',
    'motif_echec',
    'est_apercu',
    'mp3_chemin',
    'mp3_duree_secondes',
    'nombre_mots',
    'cout_estime_usd',
    'date_metadonnees',
    'date_telechargement',
    'date_extraction_audio',
    'date_transcription',
    'date_termine',
    'date_echec',
    'date_rappel_suppression',
])]
class Transcription extends Model
{
    /** @use HasFactory<TranscriptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => SourceTranscription::class,
            'statut' => StatutTranscription::class,
            'motif_echec' => MotifEchec::class,
            'est_apercu' => 'boolean',
            'duree_secondes' => 'integer',
            'mp3_duree_secondes' => 'integer',
            'nombre_mots' => 'integer',
            'cout_estime_usd' => 'decimal:4',
            'date_metadonnees' => 'datetime',
            'date_telechargement' => 'datetime',
            'date_extraction_audio' => 'datetime',
            'date_transcription' => 'datetime',
            'date_rappel_suppression' => 'datetime',
            'date_termine' => 'datetime',
            'date_echec' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Mots dans l'ordre de la vidéo. */
    public function words(): HasMany
    {
        return $this->hasMany(TranscriptionWord::class)->orderBy('position');
    }

    /** Paragraphes dans l'ordre de la vidéo. */
    public function paragraphs(): HasMany
    {
        return $this->hasMany(TranscriptionParagraph::class)->orderBy('position');
    }

    public function scriptAnalysis(): HasOne
    {
        return $this->hasOne(ScriptAnalysis::class);
    }

    /** Sorties générées par le modèle de langage (résumé, V2). */
    public function aiOutputs(): HasMany
    {
        return $this->hasMany(AiOutput::class);
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(ProcessingLog::class)->orderBy('created_at')->orderBy('id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
