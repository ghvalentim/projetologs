<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Widgets\WelcomeUserWidget;
use App\Filament\Widgets\LicenseAlertsWidget;
use App\Filament\Widgets\DepartmentDistributionChart;
use App\Filament\Widgets\SyslogChart;
use App\Filament\Pages\Auth\EditProfile;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogoHeight('3.5rem')
            ->brandLogo(asset('images/municipio.webp'))
            ->darkModeBrandLogo(asset('images/municipio-fundoescuro.webp'))
            ->brandName('Central de Logs')
            ->databaseNotifications()
            ->databaseNotificationsPolling('2s')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'secondary' => Color::Slate,
                'success' => Color::Emerald,
                'info' => Color::Blue[800],
                'warning' => Color::Yellow,
                'critical' => Color::Orange,
                'emergency' => Color::Red,
                'audit' => Color::Indigo,
                'gray' => Color::Gray,
                'exception' => Color::Lime,
            ])
            ->profile(EditProfile::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                WelcomeUserWidget::class,
                \App\Filament\Widgets\LicenseAlertsWidget::class,
                \App\Filament\Widgets\DepartmentDistributionChart::class,
                \App\Filament\Widgets\SyslogChart::class,
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
