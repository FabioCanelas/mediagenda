<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Preconsulta\FormularioPreconsulta;
use App\Http\Middleware\EstablecerUsuarioAuditoria;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('panel')
            ->brandName('MediAgenda')
            ->login()
            ->profile(isSimple: false)
            // Verificación en dos pasos con una app autenticadora y códigos de recuperación.
            // En la web es obligatoria: nadie entra sin configurarla (MFA_OBLIGATORIO).
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->brandName('MediAgenda')
                    ->recoverable(),
            ], isRequired: fn (): bool => config('mediagenda.seguridad.mfa_obligatorio'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make('Consultorio'),
                NavigationGroup::make('Configuración'),
            ])
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
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
            ])
            // HU-05: formulario público de la tablet. No pide usuario: lo abre un
            // enlace de un solo uso, con límite de intentos por minuto.
            ->routes(fn () => Route::get('/preconsulta/{token}', FormularioPreconsulta::class)
                ->where('token', '[A-Za-z0-9]{48}')
                ->middleware('throttle:30,1')
                ->name('preconsulta'))
            ->authMiddleware([
                Authenticate::class,
            ])
            // También corre en las peticiones internas de Livewire (guardar formularios).
            ->authMiddleware([
                EstablecerUsuarioAuditoria::class,
            ], isPersistent: true);
    }
}
