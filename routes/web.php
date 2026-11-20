<?php

use Illuminate\Support\Facades\Route;

// La raíz lleva directo al sistema; el panel pide iniciar sesión.
Route::redirect('/', '/panel');
