<?php

declare(strict_types=1);

return [
    'service'  => env('NOMINATIM_SERVICE', 'nominatim'),
    'language' => env('NOMINATIM_LANGUAGE'),

    'services' => [
        'nominatim' => [
            'user_agent'                 => env('NOMINATIM_USER_AGENT'),
            'email'                      => env('NOMINATIM_EMAIL'),
            'url'                        => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
            'forward_geocoding_endpoint' => env('NOMINATIM_FORWARD_GEOCODING_ENDPOINT', 'search'),
            'reverse_geocoding_endpoint' => env('NOMINATIM_REVERSE_GEOCODING_ENDPOINT', 'reverse'),
        ],

        'location_iq' => [
            'key'                        => env('LOCATION_IQ_KEY'),
            'url'                        => env('LOCATION_IQ_URL', 'https://eu1.locationiq.com/v1'),
            'forward_geocoding_endpoint' => env('LOCATION_IQ_FORWARD_GEOCODING_ENDPOINT', 'search.php'),
            'reverse_geocoding_endpoint' => env('LOCATION_IQ_REVERSE_GEOCODING_ENDPOINT', 'reverse.php'),
        ],
    ],
];
