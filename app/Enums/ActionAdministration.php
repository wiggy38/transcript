<?php

namespace App\Enums;

/** Actions du back-office inscrites au journal (journal_administration.action). */
enum ActionAdministration: string
{
    case AjustementQuota = 'ajustement_quota';
    case Remboursement = 'remboursement';
}
