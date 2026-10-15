<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upcoming Events Window (in days)
    |--------------------------------------------------------------------------
    |
    | An event is considered "upcoming" if its date falls between today and
    | today + N days (inclusive). Default is 7 days.
    |
    */
    'upcoming_days' => (int) env('UPCOMING_EVENTS_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Timezone for Event Calculations
    |--------------------------------------------------------------------------
    |
    | All comparisons for upcoming dates are performed in this timezone.
    |
    */
    'timezone' => env('UPCOMING_EVENTS_TIMEZONE', 'Asia/Jakarta'),
];
