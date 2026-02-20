<?php

return [

    /**
     * TomTom API Key
     */
    'api_key' => env('TOMTOM_API_KEY', ''),

    /**
     * TomTom SDK CDN Base
     */
    'sdk_cdn_base' => env('TOMTOM_SDK_CDN_BASE', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),

    /**
     * TomTom Geofence
     */
    'geofence' => [
        'center_lat' => (float) env('ATTENDANCE_CENTER_LAT', 0),
        'center_long' => (float) env('ATTENDANCE_CENTER_LONG', 0),
        'radius_m' => (int) env('ATTENDANCE_RADIUS_M', 100),
    ],
];
