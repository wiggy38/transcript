<?php

namespace App\Models;

use App\Enums\EtapeTraitement;
use Database\Factories\ProcessingLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une ligne par étape de traitement d'une transcription. */
#[Fillable(['transcription_id', 'etape', 'duree_ms', 'succes', 'message', 'cout_usd'])]
class ProcessingLog extends Model
{
    /** @use HasFactory<ProcessingLogFactory> */
    use HasFactory;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'etape' => EtapeTraitement::class,
            'duree_ms' => 'integer',
            'succes' => 'boolean',
            'cout_usd' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }
}
