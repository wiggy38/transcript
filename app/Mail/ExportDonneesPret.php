<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email envoyé quand l'export ZIP des données du compte est prêt, avec son lien signé. */
class ExportDonneesPret extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $nom, public string $lien, string $langue)
    {
        $this->locale($langue);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('pages.export.objet'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.export-donnees', with: [
            'nom' => $this->nom,
            'lien' => $this->lien,
            'minutes' => (int) config('voxia.donnees.duree_lien_minutes'),
            'heures' => (int) config('voxia.donnees.conservation_heures'),
        ]);
    }
}
