<?php

namespace App\Models;

use App\Enums\StatutAnalyseScript;
use Database\Factories\ScriptAnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Analyse de la structure d'un script publicitaire (F7). */
#[Fillable([
    'transcription_id',
    'statut',
    'fournisseur',
    'cout_estime_usd',
    'date_debut',
    'date_termine',
    'date_echec',
])]
class ScriptAnalysis extends Model
{
    /** @use HasFactory<ScriptAnalysisFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'statut' => StatutAnalyseScript::class,
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

    /** Les parties dans l'ordre de la vidéo. */
    public function parts(): HasMany
    {
        return $this->hasMany(ScriptPart::class)->orderBy('position');
    }
}
