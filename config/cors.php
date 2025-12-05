<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'productos/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Desarrollo
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://localhost:8000',
        'http://127.0.0.1:8000',

        // Producción
        'https://laneria-mariano-frontend.vercel.app',  
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
?>