<?php

namespace App\Models;

use Database\Factories\TranscriptionWordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un mot de transcription. Pas d'horodatage Eloquent : une transcription de
 * 3 heures compte environ 30 000 lignes, écrites en lot par le worker.
 */
#[Fillable(['transcription_id', 'position', 'texte', 'debut_ms', 'fin_ms'])]
class TranscriptionWord extends Model
{
    /** @use HasFactory<TranscriptionWordFactory> */
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
}
