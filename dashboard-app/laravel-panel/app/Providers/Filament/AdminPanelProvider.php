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
use App\Filament\Pages\Settings;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use App\Models\Setting;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

    $pollingInterval = '5';
    $savedColor = 'blue';

    try {
         $pollingInterval = Setting::where('key', 'notifications_polling_interval')->first()?->value ?? '5';
         $savedColor = Setting::where('key', 'theme_primary_color')->first()?->value ?? 'blue';
    } catch (\Exception $e) {
        
        }

        $primaryColor = match ($savedColor) {
            'emerald' => Color::Emerald,
            'amber' => Color::Amber,
            'rose' => Color::Rose,
            'indigo' => Color::Indigo[700],
            'violet' => Color::Violet,
            default => Color::Blue,
        };
                
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogoHeight('3.5rem')
            ->brandLogo(asset('images/municipio.webp'))
            ->darkModeBrandLogo(asset('images/municipio-fundoescuro.webp'))
            ->brandName('Central de Logs')
            ->databaseNotifications()
            ->databaseNotificationsPolling($pollingInterval . 's')
            ->login()
            ->colors(
                [
                'primary' => $primaryColor,
            ])
            ->profile(EditProfile::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                Settings::class,
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
