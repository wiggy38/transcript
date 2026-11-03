<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Confirmation envoyée après la suppression d'un compte. */
class CompteSupprime extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $nom)
    {
        $this->locale(app()->getLocale());
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('courriels.suppression.objet'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.suppression', with: ['nom' => $this->nom]);
    }
}
