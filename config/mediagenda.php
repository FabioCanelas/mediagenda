<?php

return [

    /*
     * Usuarios de demostración que crea UsuariosSeeder. Las contraseñas viven
     * solo en el archivo .env, que no se sube al repositorio.
     */
    'demo' => [
        'doctora' => [
            'email' => env('DEMO_DOCTORA_EMAIL', 'doctora@mediagenda.test'),
            'password' => env('DEMO_DOCTORA_PASSWORD'),
        ],
        'recepcion' => [
            'email' => env('DEMO_RECEPCION_EMAIL', 'recepcion@mediagenda.test'),
            'password' => env('DEMO_RECEPCION_PASSWORD'),
        ],
    ],

];
