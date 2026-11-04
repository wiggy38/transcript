<?php

namespace App\Models;

use App\Enums\ActionAdministration;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une ligne par action du back-office. Jamais modifiée ni supprimée depuis l'application. */
#[Fillable(['administrateur_id', 'user_id', 'transcription_id', 'action', 'motif', 'details'])]
class JournalAdministration extends Model
{
    protected $table = 'journal_administration';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'action' => ActionAdministration::class,
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrateur_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }
}
