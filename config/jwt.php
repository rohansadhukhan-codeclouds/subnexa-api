<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT signing secret
    |--------------------------------------------------------------------------
    |
    | Defaults to APP_KEY. Prefer a dedicated JWT_SECRET in production if you
    | rotate APP_KEY independently of issued admin tokens.
    |
    */

    'secret' => env('JWT_SECRET', env('APP_KEY')),

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Token lifetime (minutes)
    |--------------------------------------------------------------------------
    */

    'ttl' => (int) env('JWT_TTL', 60 * 12),

    'issuer' => env('JWT_ISSUER', env('APP_URL', 'subnexa')),

];
