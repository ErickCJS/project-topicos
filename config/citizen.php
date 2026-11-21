<?php

return [
    /*
    | GPS readings above this accuracy are rejected as insufficient for a
    | citizen zone decision. This is a configurable operational threshold,
    | not a guarantee of physical positioning accuracy.
    */
    'gps' => [
        'max_accuracy_meters' => (float) env('CITIZEN_GPS_MAX_ACCURACY_METERS', 100),
    ],

    'tracking' => [
        'stale_seconds' => (int) env('CITIZEN_TRACKING_STALE_SECONDS', 120),
        'low_accuracy_meters' => (float) env('CITIZEN_TRACKING_LOW_ACCURACY_METERS', 50),
    ],
];
