<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shipping (all amounts in paisa: PKR 350 = 35000)
    |--------------------------------------------------------------------------
    |
    | city_rates overrides the standard courier price per delivery city. Keys are
    | lower-case city names as customers type them at checkout, e.g.
    |   'lahore' => 25000, 'karachi' => 35000, 'islamabad' => 30000,
    | Fill these in with your courier's real rates; unlisted cities pay standard_paisa.
    | Orders at or above free_threshold_paisa ship free by standard courier.
    |
    */

    'shipping' => [
        'standard_paisa' => (int) env('SHIPPING_STANDARD_PAISA', 35000),
        'express_paisa' => (int) env('SHIPPING_EXPRESS_PAISA', 65000),
        'free_threshold_paisa' => (int) env('SHIPPING_FREE_THRESHOLD_PAISA', 1000000),
        'city_rates' => [
            // 'lahore' => 25000,
        ],
    ],

];
