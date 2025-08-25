<?php

return [
    'unit_converter' => [
        'enabled' => true,
        'free_limit' => null, // unlimited
        'premium_features' => ['custom_units', 'bulk_conversion'],
        'supported_categories' => [
            'length' => ['meter', 'kilometer', 'inch', 'foot', 'yard', 'mile'],
            'weight' => ['gram', 'kilogram', 'pound', 'ounce', 'ton'],
            'temperature' => ['celsius', 'fahrenheit', 'kelvin'],
            'area' => ['square_meter', 'square_foot', 'acre', 'hectare'],
            'volume' => ['liter', 'gallon', 'cup', 'pint', 'quart'],
        ]
    ],
    
    'qr_generator' => [
        'enabled' => true,
        'free_limit' => null, // unlimited
        'premium_features' => ['custom_logo', 'bulk_generation'],
    ],
    
    'uuid_generator' => [
        'enabled' => true,
        'free_limit' => null, // unlimited
        'premium_features' => ['bulk_generation'],
    ],
];