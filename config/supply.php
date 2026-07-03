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
    'email_ingestion' => [
        'default_provider' => env('SUPPLY_EMAIL_PROVIDER', 'manual'),
        'default_analyzer' => env('SUPPLY_EMAIL_ANALYZER', 'rule_based'),
        'auto_analyze_inbound' => env('SUPPLY_EMAIL_AUTO_ANALYZE', false),
    ],
    'ai' => [
        'email_extraction_requires_human_review_by_default' => true,
        'email_extraction_min_confidence' => 0.80,
    ],
];
