<?php

namespace App\Enums;

/** Formats de réécriture (V2) : un format = une sortie = 1 crédit IA. Gabarits : resources/ia/reecriture/. */
enum FormatReecriture: string
{
    case Article = 'article';
    case Newsletter = 'newsletter';
    case LinkedIn = 'linkedin';
    case Thread = 'thread';

    /** Nom du format dans le prompt (en français, comme le reste des prompts). */
    public function nomPrompt(): string
    {
        return match ($this) {
            self::Article => 'un article de blog',
            self::Newsletter => 'une newsletter',
            self::LinkedIn => 'un post LinkedIn',
            self::Thread => 'un thread X',
        };
    }
}
