<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Contraseñas largas y variadas para todos los usuarios.
        Password::defaults(fn (): Password => Password::min(12)->letters()->mixedCase()->numbers()->symbols());

        // En producción: solo HTTPS y sin comandos que borren la base (migrate:fresh, db:wipe).
        URL::forceHttps($this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Ajustes sutiles al panel: pestañas y acciones que se noten como botones.
        // "php artisan filament:assets" lo publica en public/css/app/ (en Docker lo hace composer).
        FilamentAsset::register([
            Css::make('ajustes-panel', resource_path('css/filament/ajustes-panel.css')),
        ]);
    }
}
