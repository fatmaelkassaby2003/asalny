<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-sidebar.css') . '" />'
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-sidebar-dark.css') . '" />'
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'JS'
                    <script>
                        try {
                            localStorage.removeItem('filament.admin.sidebar.group.collapsed_groups');
                        } catch (e) {}
                    </script>
                JS
            )
            ->font('Cairo')
            ->brandLogo(fn () => view('filament.logo'))
            ->brandLogoHeight('3rem')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('الدعم')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('العمليات')
                    ->icon('heroicon-o-briefcase')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('الإدارة')
                    ->icon('heroicon-o-user-group')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('المالية')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(true),
            ])
            ->collapsibleNavigationGroups(true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\CustomAccountWidget::class,
                \App\Filament\Widgets\AsalnyWelcomeWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
