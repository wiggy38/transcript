<?php

namespace App\Enums;

enum StatutTranscription: string
{
    case EnAttente = 'en_attente';
    case Telechargement = 'telechargement';
    case Transcription = 'transcription';
    case Termine = 'termine';
    case Echec = 'echec';
}
