<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

/** Accueil du back-office : indicateurs du jour et des 30 derniers jours (widgets découverts). */
class TableauDeBord extends Dashboard
{
    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string
    {
        return __('administration.navigation.tableau');
    }

    public function getTitle(): string
    {
        return __('administration.tableau.titre');
    }
}
