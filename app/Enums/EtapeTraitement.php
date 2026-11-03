<?php

namespace App\Enums;

enum EtapeTraitement: string
{
    case Metadonnees = 'metadonnees';
    case Telechargement = 'telechargement';
    case ExtractionAudio = 'extraction_audio';
    case Transcription = 'transcription';
    case AnalyseScript = 'analyse_script';
}
