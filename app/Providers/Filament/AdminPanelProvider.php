<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Cyan,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
            ->navigationGroups([
                'Gestión de Casos',
                'Seguimiento',
                'Reportes',
                'Configuración',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
            // // ✅ GRUPOS DE NAVEGACIÓN ORGANIZADOS
            // ->navigationGroups([
            //     'Gestión de Pacientes' => [
            //         'label' => 'Gestión de Pacientes',
            //         'icon' => 'heroicon-o-user-group',
            //         'collapsible' => true,
            //         'collapsed' => false,
            //     ],
            //     'Gestión de Casos' => [
            //         'label' => 'Gestión de Casos',
            //         'icon' => 'heroicon-o-clipboard-document-list',
            //         'collapsible' => true,
            //         'collapsed' => false,
            //     ],
            //     'Reportes' => [
            //         'label' => 'Reportes y Analytics',
            //         'icon' => 'heroicon-o-chart-bar',
            //         'collapsible' => true,
            //         'collapsed' => false,
            //     ],
            //     'Administración' => [
            //         'label' => 'Administración del Sistema',
            //         'icon' => 'heroicon-o-cog-6-tooth',
            //         'collapsible' => true,
            //         'collapsed' => true,
            //     ],
            // ])
            // ✅ CONFIGURACIONES AVANZADAS
            // ->spa() 
            // ->sidebarCollapsibleOnDesktop()
            // ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            // ->globalSearch()
            // ->databaseNotifications()
            // ->databaseNotificationsPolling('30s')
            // // ✅ CONFIGURACIÓN DE PERFIL DE USUARIO
            // ->userMenuItems([
            //     'profile' => \Filament\Pages\Auth\EditProfile::class,
            //     // 'logout' => \Filament\Http\Livewire\Auth\Logout::class,
            // ])
            // ✅ CONFIGURACIONES DE TEMA
            // ->darkMode(false)
            // ->topNavigation(false)
            // ->sidebarWidth('16rem')
            // // ✅ CONFIGURACIÓN DE BREADCRUMBS
            // ->breadcrumbs(true)
            // // ✅ CONFIGURACIÓN DE TENANCY (si se requiere en el futuro)
            // ->tenant(null);
    }
}
