<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // Permet toutes les méthodes HTTP (GET, POST, etc.)

     'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),// Autoriser votre frontend Angular

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'], // Autoriser les en-têtes spécifiques si nécessaires

    'max_age' => 0,

    'supports_credentials' => true, // Autoriser les cookies et autres informations sensibles
];
