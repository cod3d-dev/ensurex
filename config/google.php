<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for Google APIs
    |
    */

    'maps' => [
        'geocoding_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],
];
