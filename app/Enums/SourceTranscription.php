<?php

namespace App\Enums;

enum SourceTranscription: string
{
    case Youtube = 'youtube';
    case Tiktok = 'tiktok';
    case Fichier = 'fichier';
}
