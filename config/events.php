<?php

return [
    'environment' => env(
        'EVENT_DOMAIN_ENVIRONMENT',
        env('APP_ENV') === 'production' ? 'production' : 'local'
    ),
];
