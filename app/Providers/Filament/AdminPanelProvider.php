<?php

namespace App\Providers\Filament;

use App\Filament\Pages\TableauDeBord;
use App\Http\Middleware\DefinirLangue;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
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
                // Palettes de la direction « Nocturne » (tokens de resources/css/voxia-tokens.css).
                // primary : vert citron en sombre (500), olive en clair (600) pour le contraste du texte blanc.
                'primary' => [
                    50 => '#F6FCE2', 100 => '#EEF8C8', 200 => '#DFF3A0', 300 => '#D1EE7C', 400 => '#C8F25A',
                    500 => '#C8F25A', 600 => '#4A6A00', 700 => '#3E5700', 800 => '#334700', 900 => '#283900', 950 => '#1E2B00',
                ],
                // gray : fonds et filets de Nocturne, du clair (50) au fond sombre (950).
                'gray' => [
                    50 => '#F6F5FF', 100 => '#EEEBFF', 200 => '#DDDAF2', 300 => '#C0BAE6', 400 => '#9690C4',
                    500 => '#6E68A0', 600 => '#423D66', 700 => '#2C2750', 800 => '#1D1938', 900 => '#15122A', 950 => '#0D0B1A',
                ],
            ])
            // Nocturne sombre uniquement : pas de bascule vers le clair dans l'administration.
            ->darkMode(true, isForced: true)
            ->pages([
                TableauDeBord::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.pied-page'))
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
