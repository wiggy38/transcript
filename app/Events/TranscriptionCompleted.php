<?php

namespace App\Events;

use App\Models\Transcription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Transcription terminée. Point d'accroche : la consommation (quota) et les
 * notifications s'y branchent ; rien d'autre n'écrit de consommation.
 */
final class TranscriptionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Transcription $transcription) {}
}
