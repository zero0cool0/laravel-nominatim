<?php

declare(strict_types=1);

return [
    'service'  => env('NOMINATIM_SERVICE', 'nominatim'),
    'language' => env('NOMINATIM_LANGUAGE'),

    'services' => [
        'nominatim' => [
            'user_agent'                 => env('NOMINATIM_NOMINATIM_USER_AGENT'),
            'email'                      => env('NOMINATIM_NOMINATIM_EMAIL'),
            'url'                        => env('NOMINATIM_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
            'forward_geocoding_endpoint' => env('NOMINATIM_NOMINATIM_FORWARD_GEOCODING_ENDPOINT', 'search'),
            'reverse_geocoding_endpoint' => env('NOMINATIM_NOMINATIM_REVERSE_GEOCODING_ENDPOINT', 'reverse'),
        ],

        'location_iq' => [
            'key'                        => env('NOMINATIM_LOCATION_IQ_KEY'),
            'url'                        => env('NOMINATIM_LOCATION_IQ_URL', 'https://eu1.locationiq.com/v1'),
            'forward_geocoding_endpoint' => env('NOMINATIM_LOCATION_IQ_FORWARD_GEOCODING_ENDPOINT', 'search.php'),
            'reverse_geocoding_endpoint' => env('NOMINATIM_LOCATION_IQ_REVERSE_GEOCODING_ENDPOINT', 'reverse.php'),
        ],
    ],
];
