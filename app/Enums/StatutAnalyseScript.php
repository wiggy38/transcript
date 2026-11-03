<?php

namespace App\Enums;

enum StatutAnalyseScript: string
{
    case EnAttente = 'en_attente';
    case Analyse = 'analyse';
    case Termine = 'termine';
    case Echec = 'echec';
}
