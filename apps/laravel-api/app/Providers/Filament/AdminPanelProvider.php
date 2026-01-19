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
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Statikbe\FilamentFlexibleContentBlockPages\FlexibleContentBlockPagesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Go Adventure - Admin')
            ->colors([
                'primary' => '#0D642E', // Dark forest green from design system
            ])
            ->navigationGroups([
                NavigationGroup::make('Sales'),
                NavigationGroup::make('Operations')
                    ->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make('People')
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make('Catalog')
                    ->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make('Content')
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make('Marketing')
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make('System')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make('Compliance')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->plugins([
                FlexibleContentBlockPagesPlugin::make(),
                SpatieLaravelTranslatablePlugin::make()
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
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('filament.locale.switch', ['locale' => app()->getLocale() === 'en' ? 'fr' : 'en'])),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}
