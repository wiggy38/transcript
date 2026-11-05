<?php

namespace App\Enums;

/** Ton de la réécriture. La consigne de chaque ton est dans resources/ia/reecriture/tons.php. */
enum TonReecriture: string
{
    case Professionnel = 'professionnel';
    case Conversationnel = 'conversationnel';
    case Enthousiaste = 'enthousiaste';
}
