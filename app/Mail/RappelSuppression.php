<?php

namespace App\Mail;

use App\Models\Transcription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Rappel envoyé avant la suppression des transcriptions du plan Gratuit (cahier F5).
 * Les dates de suppression sont calculées à partir de la durée de conservation du plan.
 */
class RappelSuppression extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, Transcription>  $transcriptions */
    public function __construct(
        public User $utilisateur,
        public Collection $transcriptions,
        public int $joursConservation,
        public int $joursAvantRappel,
    ) {
        $this->locale($utilisateur->locale ?? config('app.locale'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('courriels.rappel_suppression.objet'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.rappel-suppression', with: [
            'nom' => $this->utilisateur->name,
            'lignes' => $this->transcriptions->map(fn ($transcription) => [
                'titre' => $transcription->titre ?? $transcription->url_origine ?? '—',
                'date' => $transcription->created_at->copy()->addDays($this->joursConservation)->format(__('historique.format_date')),
            ])->all(),
            'nombre' => $this->transcriptions->count(),
            'jours' => $this->joursConservation,
            'avant' => $this->joursAvantRappel,
            'url' => route('historique'),
        ]);
    }
}
