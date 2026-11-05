<?php

namespace App\Models;

use App\Enums\Plan;
use App\Enums\UniteConsommation;
use Database\Factories\UsageRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Journal de consommation : une ligne par débit. Les lignes ne se modifient
 * jamais ; un remboursement est une ligne inverse créée par reverse().
 */
#[Fillable([
    'user_id',
    'transcription_id',
    'script_analysis_id',
    'ai_output_id',
    'annule_usage_record_id',
    'unite',
    'plan',
    'periode',
    'pack_achat_id',
    'quantite',
    'motif',
])]
class UsageRecord extends Model
{
    /** @use HasFactory<UsageRecordFactory> */
    use HasFactory;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'unite' => UniteConsommation::class,
            'plan' => Plan::class,
            'periode' => 'date:Y-m-d',
            'quantite' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** Pack dont cette ligne consomme les secondes (null hors Pack). */
    public function packAchat(): BelongsTo
    {
        return $this->belongsTo(PackAchat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }

    public function scriptAnalysis(): BelongsTo
    {
        return $this->belongsTo(ScriptAnalysis::class);
    }

    /** La ligne que ce remboursement annule, si c'en est un. */
    public function annule(): BelongsTo
    {
        return $this->belongsTo(self::class, 'annule_usage_record_id');
    }

    /** Le remboursement qui annule cette ligne, s'il existe. */
    public function remboursement(): HasOne
    {
        return $this->hasOne(self::class, 'annule_usage_record_id');
    }

    /**
     * Annule cette consommation par une ligne en sens inverse.
     * Refuse d'annuler un remboursement ou une ligne déjà annulée.
     */
    public function reverse(?string $motif = null): self
    {
        return DB::transaction(function () use ($motif) {
            $original = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($original->annule_usage_record_id !== null) {
                throw new LogicException('Une ligne de remboursement ne peut pas être annulée.');
            }

            if ($original->remboursement()->exists()) {
                throw new LogicException('Cette consommation a déjà été remboursée.');
            }

            return self::create([
                'user_id' => $original->user_id,
                'transcription_id' => $original->transcription_id,
                'script_analysis_id' => $original->script_analysis_id,
                'ai_output_id' => $original->ai_output_id,
                'annule_usage_record_id' => $original->getKey(),
                'unite' => $original->unite,
                'plan' => $original->plan,
                'periode' => $original->periode,
                'pack_achat_id' => $original->pack_achat_id,
                'quantite' => -$original->quantite,
                'motif' => $motif,
            ]);
        });
    }
}
