<?php

namespace App\Enums;

enum MotifEchec: string
{
    case LienInvalide = 'lien_invalide';
    case VideoPrivee = 'video_privee';
    case VideoSupprimee = 'video_supprimee';
    case BloqueeRegion = 'bloquee_region';
    case LiveEnCours = 'live_en_cours';
    case TropLongue = 'trop_longue';
    case SansAudio = 'sans_audio';
    case ErreurTechnique = 'erreur_technique';
}
