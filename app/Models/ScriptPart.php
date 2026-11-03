<?php

namespace App\Models;

use App\Enums\TypePartieScript;
use Database\Factories\ScriptPartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une des cinq parties d'un script. Bornes nulles si la partie n'est pas détectée. */
#[Fillable(['script_analysis_id', 'position', 'type', 'debut_ms', 'fin_ms', 'texte', 'detectee'])]
class ScriptPart extends Model
{
    /** @use HasFactory<ScriptPartFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => TypePartieScript::class,
            'debut_ms' => 'integer',
            'fin_ms' => 'integer',
            'detectee' => 'boolean',
        ];
    }

    public function scriptAnalysis(): BelongsTo
    {
        return $this->belongsTo(ScriptAnalysis::class);
    }
}
