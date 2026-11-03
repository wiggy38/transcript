<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Supprime les fichiers (MP3) des transcriptions d'un compte supprimé. Chaque transcription
 * a son dossier « transcriptions/{id} » sur le disque de stockage (cahier §5, suppression).
 */
class SupprimerFichiersTranscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param  list<int>  $transcriptionIds */
    public function __construct(public array $transcriptionIds) {}

    public function handle(): void
    {
        $disque = Storage::disk((string) config('voxia.stockage.disque'));

        foreach ($this->transcriptionIds as $identifiant) {
            $disque->deleteDirectory('transcriptions/'.$identifiant);
        }
    }
}
