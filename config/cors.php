<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Profile
    |--------------------------------------------------------------------------
    |
    | Here you may configure the cross-origin resource sharing settings for
    | your application. This simply determines what origins are allowed to
    | access your application's resources via cross-origin requests.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://9000-firebase-dream-1763315369962.cluster-ikslh4rdsnbqsvu5nw3v4dqjj2.cloudworkstations.dev',
    'https://mykanta.netlify.app'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
