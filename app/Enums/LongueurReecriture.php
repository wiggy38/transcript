<?php

namespace App\Enums;

/** Longueur de l'article (voxia.ia.reecriture.article.mots). Les autres formats ont une longueur fixe. */
enum LongueurReecriture: string
{
    case Courte = 'courte';
    case Moyenne = 'moyenne';
    case Longue = 'longue';
}
