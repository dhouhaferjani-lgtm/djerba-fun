<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetFilamentLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->login()
            ->brandName('Go Adventure - Vendor Portal')
            ->colors([
                'primary' => '#0D642E', // Dark forest green from design system
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('filament-panels.navigation.groups.my_listings'))
                    ->icon('heroicon-o-map'),
                NavigationGroup::make()
                    ->label(__('filament-panels.navigation.groups.bookings'))
                    ->icon('heroicon-o-calendar'),
                NavigationGroup::make()
                    ->label(__('filament-panels.navigation.groups.feedback'))
                    ->icon('heroicon-o-star'),
                NavigationGroup::make()
                    ->label(__('filament-panels.navigation.groups.finance'))
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make()
                    ->label(__('filament-panels.navigation.groups.catalog'))
                    ->icon('heroicon-o-squares-2x2'),
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->plugins([
                \Filament\SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['en', 'fr']),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetFilamentLocale::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => app()->getLocale() === 'en' ? 'Français' : 'English')
                    ->icon(fn () => app()->getLocale() === 'en' ? 'heroicon-o-language' : 'heroicon-o-language')
                    ->url(fn () => route('filament.locale.switch', ['locale' => app()->getLocale() === 'en' ? 'fr' : 'en'])),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}
