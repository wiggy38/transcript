<?php

namespace App\Enums;

/** Présence des chapitres dans une sortie : jamais (vidéo courte), facultatifs (morceau), requis (synthèse longue). */
enum ModeChapitres: string
{
    case Aucun = 'aucun';
    case Facultatifs = 'facultatifs';
    case Requis = 'requis';
}
