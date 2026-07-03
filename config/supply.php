<?php

return [
    'email' => [
        'default_sender' => env('SUPPLY_EMAIL_SENDER', 'log'),
        'default_from' => env('SUPPLY_EMAIL_FROM'),
    ],
    'exports' => [
        'supplier_orders_path' => 'exports/supplier-orders',
        'default_supplier_order_format' => 'excel_csv',
    ],
];
