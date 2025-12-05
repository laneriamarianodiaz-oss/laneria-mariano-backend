<?php

return [
    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie',
        'productos/*'  // ⭐ AGREGAR ESTA LÍNEA
    ],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://localhost:8000',  // ⭐ AGREGAR
        'http://127.0.0.1:8000',  // ⭐ AGREGAR
    ],
    
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];