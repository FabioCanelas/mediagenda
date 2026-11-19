<?php

return [

    /*
     * Usuarios de demostración que crea UsuariosSeeder. Las contraseñas viven
     * solo en el archivo .env, que no se sube al repositorio.
     */
    /*
     * Jornada de atención para sugerir horarios libres en la agenda.
     */
    'agenda' => [
        'hora_inicio' => env('AGENDA_HORA_INICIO', '08:00'),
        'hora_fin' => env('AGENDA_HORA_FIN', '19:00'),
        'intervalo_minutos' => (int) env('AGENDA_INTERVALO_MINUTOS', 15),
    ],

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
