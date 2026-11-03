<?php

namespace App\Events;

use App\Enums\MotifEchec;
use App\Models\Transcription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Transcription passée en échec, avec son motif. Point d'accroche des notifications. */
final class TranscriptionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Transcription $transcription,
        public MotifEchec $motif,
    ) {}
}
