<?php

namespace App\Providers\Filament;

use App\Http\Middleware\KeepCollectorsOutOfAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationItem;


class AdminPanelProvider extends PanelProvider
{
    protected static bool $isLazy = true;
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->spa()
            ->id('admin')
            ->path('admin')
            ->login()
            //->registration()
            ->passwordReset()
            ->profile()
            // ->emailVerification()
            ->brandName('Milk Collection System')
            ->globalSearch(false)
            ->sidebarCollapsibleOnDesktop()
            ->font('Poppins')                   // Custom typography
            ->favicon(asset('images/logo.png'))
            ->unsavedChangesAlerts()
            // ->databaseNotifications()       // Prevents accidental data loss on forms
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                //FilamentInfoWidget::class,
            ])
            // AdminPanelProvider.php


            ->navigationItems([
                NavigationItem::make('Collector page')
                    ->url('/collector', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-truck')
                    ->group('Operations') // optional, groups it under a section
                    ->sort(1),
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
                KeepCollectorsOutOfAdmin::class,
            ]);
    }
}
