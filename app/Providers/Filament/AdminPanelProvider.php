<?php

namespace App\Providers\Filament;

use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\QuoteResource;
use App\Filament\Widgets;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->unsavedChangesAlerts()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topNavigation()
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\LatestQuotes::class,
                // Widgets\LatestPolicies::class,
                // Widgets\LatestDocs::class,
                // Widgets\LatestPolicies::class,
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
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Cotizaciones')
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make()
                    ->label('Pólizas')
                    ->icon('iconoir-privacy-policy'),
                NavigationGroup::make()
                    ->label('Ajustes')
                    ->icon('heroicon-o-cog'),

            ])
            ->navigationItems([
                NavigationItem::make('Mis Cotizaciones')
                    ->url(fn () => QuoteResource::getUrl('index').'?tableFilters[user_id][value]='.auth()->user()->id)
                    ->group('Cotizaciones')
                    ->sort(1),
                NavigationItem::make('Todas las Cotizaciones')
                    ->url(fn () => QuoteResource::getUrl('index'))
                    ->group('Cotizaciones')
                    ->sort(2),
                NavigationItem::make('Cotizar')
                    ->url(fn () => QuoteResource::getUrl('create'))
                    ->group('Cotizaciones')
                    ->sort(3),
                NavigationItem::make('Mis Pólizas')
                    ->url(fn () => PolicyResource::getUrl('index').'?tableFilters[user_id][value]='.auth()->user()->id)
                    ->group('Pólizas')
                    ->sort(1),
                NavigationItem::make('Problemas')
                    ->url(fn () => PolicyResource::getUrl('index'))
                    ->group('Pólizas')
                    ->sort(3),
                NavigationItem::make('Documentos')
                    ->url(fn () => PolicyResource::getUrl('index'))
                    ->group('Pólizas')
                    ->sort(4),
                NavigationItem::make('Todas las Pólizas')
                    ->url(fn () => PolicyResource::getUrl('index'))
                    ->group('Pólizas')
                    ->sort(5),
                NavigationItem::make('Comisiones')
                    ->url(fn () => PolicyResource::getUrl('index'))
                    ->group('Pólizas')
                    ->visible(fn () => auth()->user()?->role === \App\Enums\UserRoles::Admin)
                    ->sort(6),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook('panels::body.end', fn (): string => Blade::render("@vite('resources/js/app.js')"));
    }
}
