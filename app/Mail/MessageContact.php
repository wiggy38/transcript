<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Message du formulaire de contact, envoyé à l'adresse de l'équipe (config voxia.contact.email).
 * Le texte est envoyé brut, en texte seul ; l'adresse du visiteur sert de réponse.
 */
class MessageContact extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nom,
        public string $adresse,
        public string $sujet,
        public string $contenu,
        string $langue,
    ) {
        $this->locale($langue);
        $this->replyTo($adresse, $nom);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('pages.contact_message.objet', ['sujet' => __('pages.contact.sujets.'.$this->sujet)]));
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact-texte');
    }
}
