<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un achat de Pack : des secondes de transcription, valables jusqu'à expire_at. Le solde
 * restant se calcule à partir des lignes de consommation qui pointent vers l'achat.
 */
#[Fillable(['user_id', 'secondes', 'achete_at', 'expire_at'])]
class PackAchat extends Model
{
    protected function casts(): array
    {
        return [
            'secondes' => 'integer',
            'achete_at' => 'datetime',
            'expire_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
