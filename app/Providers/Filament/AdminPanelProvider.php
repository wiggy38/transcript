<?php

namespace App\Providers\Filament;

use App\Filament\Pages\TableauDeBord;
use App\Http\Middleware\DefinirLangue;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Back-office Voxia sur /admin (cahier §5). Réservé aux comptes is_admin : voir User::canAccessPanel().
 * Thème sombre de Filament, direction « Nocturne » (resources/css/filament/admin/theme.css).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(__('administration.marque'))
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::hex('#C8F25A'),
                'gray' => Color::Indigo,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->pages([
                TableauDeBord::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                DefinirLangue::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
