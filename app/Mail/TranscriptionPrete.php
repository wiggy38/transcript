<?php

namespace App\Mail;

use App\Models\Transcription;
use App\Support\Horodatage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email envoyé quand une transcription longue est terminée. */
class TranscriptionPrete extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Transcription $transcription)
    {
        $this->locale($transcription->user->locale ?? config('app.locale'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('courriels.transcription_prete.objet'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.transcription-prete', with: [
            'nom' => $this->transcription->user->name,
            'titre' => $this->transcription->titre ?? $this->transcription->url_origine ?? '—',
            'duree' => Horodatage::format((int) $this->transcription->duree_secondes * 1000),
            'url' => route('transcriptions.show', $this->transcription),
        ]);
    }
}
