<?php

return [

    /*
     * Jornada de atención para sugerir horarios libres en la agenda.
     */
    'agenda' => [
        'hora_inicio' => env('AGENDA_HORA_INICIO', '08:00'),
        'hora_fin' => env('AGENDA_HORA_FIN', '19:00'),
        'intervalo_minutos' => (int) env('AGENDA_INTERVALO_MINUTOS', 15),
    ],

    /*
     * Membrete de las recetas y los consentimientos. Los valores por defecto
     * son ficticios: se cambian en el .env con los datos reales de la doctora.
     */
    'consultorio' => [
        'doctora' => env('CONSULTORIO_DOCTORA', 'Dra. Demo Alergóloga'),
        'especialidad' => env('CONSULTORIO_ESPECIALIDAD', 'Alergología e Inmunología Clínica'),
        'matricula' => env('CONSULTORIO_MATRICULA', 'Mat. Prof. DEMO-0000'),
        'direccion' => env('CONSULTORIO_DIRECCION', 'Av. Ejemplo 123, consultorio 4'),
        'telefono' => env('CONSULTORIO_TELEFONO', '700-00000'),
        'ciudad' => env('CONSULTORIO_CIUDAD', 'La Paz, Bolivia'),
    ],

    /*
     * Pre-consulta en tablet (HU-05): el enlace sirve una sola vez y vence.
     */
    'preconsulta' => [
        'horas_vigencia' => (int) env('PRECONSULTA_HORAS_VIGENCIA', 3),
    ],

    /*
     * Estudios adjuntos (HU-08).
     */
    'adjuntos' => [
        'tamano_maximo_kb' => (int) env('ADJUNTOS_TAMANO_MAXIMO_KB', 10240),
    ],

    /*
     * Disco privado para estudios y PDF emitidos: "local" en la computadora,
     * "supabase" (Supabase Storage) en la web, donde el disco del servidor se borra.
     */
    'archivos' => [
        'disco' => env('ARCHIVOS_DISCO', 'local'),
    ],

    /*
     * Seguridad en producción. La verificación en dos pasos se vuelve obligatoria
     * para todos los usuarios cuando MFA_OBLIGATORIO=true (así está en Render).
     */
    'seguridad' => [
        'mfa_obligatorio' => (bool) env('MFA_OBLIGATORIO', false),
    ],

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
