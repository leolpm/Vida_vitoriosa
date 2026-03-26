<?php

return [
    'relationships' => [
        'Pai',
        'Mãe',
        'Irmão',
        'Irmã',
        'Avô',
        'Avó',
        'Tio',
        'Tia',
        'Primo',
        'Prima',
        'Amigo',
        'Amiga',
        'Líder',
        'Pastor',
        'Cônjuge',
        'Filho',
        'Filha',
        'Outro',
    ],

    'testimonial_upload_max_kb' => 10240,
    'login_code_expires_minutes' => 15,
    'pdf_default_mode' => 'only_new',
    'dev_server_port' => env('APP_SERVE_PORT', 8888),
];
