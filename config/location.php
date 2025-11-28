<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum Login Distance
    |--------------------------------------------------------------------------
    |
    | The maximum distance (in kilometers) a caregiver can be from their
    | assigned branch or facility to log in.
    | Default: 0.05 km (50 meters)
    |
    */
    'max_login_distance_km' => env('MAX_LOGIN_DISTANCE_KM', 0.05),

    /*
    |--------------------------------------------------------------------------
    | Geocoding Service
    |--------------------------------------------------------------------------
    |
    | The geocoding service to use for converting addresses to coordinates.
    | Options: 'nominatim' (OpenStreetMap - free, no API key)
    |
    */
    'geocoding_service' => env('GEOCODING_SERVICE', 'nominatim'),

    /*
    |--------------------------------------------------------------------------
    | IP Geolocation Service
    |--------------------------------------------------------------------------
    |
    | The IP geolocation service to use as fallback when browser geolocation
    | is not available. Options: 'ipapi' (ip-api.com - free tier available)
    |
    */
    'ip_geolocation_service' => env('IP_GEOLOCATION_SERVICE', 'ipapi'),

    /*
    |--------------------------------------------------------------------------
    | Geocoding Rate Limit
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for geocoding API calls to prevent abuse.
    | OpenStreetMap Nominatim requires max 1 request per second.
    |
    */
    'geocoding_rate_limit' => [
        'max_requests' => env('GEOCODING_MAX_REQUESTS', 1),
        'per_seconds' => env('GEOCODING_PER_SECONDS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Agent for Geocoding Requests
    |--------------------------------------------------------------------------
    |
    | User agent string to use when making geocoding API requests.
    | OpenStreetMap Nominatim requires a proper user agent.
    |
    */
    'geocoding_user_agent' => env('GEOCODING_USER_AGENT', 'Evergreen Location Service'),

    /*
    |--------------------------------------------------------------------------
    | Enable Location Check
    |--------------------------------------------------------------------------
    |
    | Master switch to enable/disable location-based login restrictions.
    | When disabled, all location checks are bypassed.
    |
    */
    'enabled' => env('LOCATION_CHECK_ENABLED', true),
];

