<?php

namespace App\Providers;

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
    }
}
