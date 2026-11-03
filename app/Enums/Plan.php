<?php

namespace App\Enums;

/** Clés des plans dans config/voxia.php (« plans »). */
enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Pack = 'pack';
    case Business = 'business';
}
