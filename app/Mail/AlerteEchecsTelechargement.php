<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Alerte envoyée à l'administrateur quand les téléchargements échouent trop souvent (back-office). */
class AlerteEchecsTelechargement extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $administrateur,
        public float $taux,
        public int $tentatives,
        public int $echecs,
        public float $seuil,
        public int $fenetreMinutes,
    ) {
        $this->locale('fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('administration.courriel.alerte_echecs.objet', [], 'fr'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.administration.alerte-echecs', with: [
            'nom' => $this->administrateur->name,
            'taux' => round($this->taux * 100, 1),
            'seuil' => round($this->seuil * 100, 1),
            'tentatives' => $this->tentatives,
            'echecs' => $this->echecs,
            'fenetre' => $this->fenetreMinutes,
            'url' => url('/admin'),
        ]);
    }
}
