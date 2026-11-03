<?php

namespace App\Listeners;

use App\Events\TranscriptionCompleted;
use App\Mail\TranscriptionPrete;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie un email quand une vidéo de plus de 15 minutes est prête (cahier F1). Les vidéos
 * plus courtes n'en reçoivent pas : l'utilisateur attend la fin sur l'écran.
 */
class PrevenirFinVideoLongue implements ShouldQueue
{
    public function handle(TranscriptionCompleted $evenement): void
    {
        $transcription = $evenement->transcription;
        $seuil = (int) config('voxia.notifications.long_video_email_after_seconds');

        if ((int) $transcription->duree_secondes <= $seuil) {
            return;
        }

        $transcription->loadMissing('user');

        Mail::to($transcription->user)->send(new TranscriptionPrete($transcription));
    }
}
