<?php

return [
    'demo_mode' => env('CAFE_ISLA_DEMO_MODE', in_array(env('APP_ENV'), ['local', 'testing'], true)),
    'local_delivery_areas' => explode(';', (string) env(
        'CAFE_ISLA_LOCAL_DELIVERY_AREAS',
        'Dasmariñas, Cavite;San Juan, La Union',
    )),
];
