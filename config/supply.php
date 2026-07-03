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
    'form_autofill' => [
        'default_extractor' => env('SUPPLY_FORM_AUTOFILL_EXTRACTOR', 'rule_based'),
        'overall_min_confidence' => 0.80,
        'required_field_min_confidence' => 0.85,
        'date_field_min_confidence' => 0.90,
        'quantity_field_min_confidence' => 0.90,
        'sku_field_min_confidence' => 0.90,
        'currency_field_min_confidence' => 0.85,
        'human_review_by_default' => true,
    ],
];
