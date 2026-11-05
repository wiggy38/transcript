<?php

namespace App\Enums;

/** Pourquoi un appel au modèle de langage a échoué. La valeur est écrite dans appels_ia.statut. */
enum MotifErreurModele: string
{
    case Delai = 'delai';
    case Debit = 'debit';
    case Connexion = 'connexion';
    case Http = 'http';
    case Refus = 'refus';
    case Tronquee = 'tronquee';
    case ReponseInvalide = 'reponse_invalide';
    case AucunContenu = 'aucun_contenu';
    case CleAbsente = 'cle_absente';
    case ModeleInconnu = 'modele_inconnu';
}
