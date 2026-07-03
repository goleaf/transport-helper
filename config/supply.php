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
    'logistics' => [
        'expected_soon_days' => env('SUPPLY_LOGISTICS_EXPECTED_SOON_DAYS', 3),
        'missing_confirmation_after_days' => env('SUPPLY_MISSING_CONFIRMATION_AFTER_DAYS', 5),
        'ready_date_missing_after_confirmation_days' => env('SUPPLY_READY_DATE_MISSING_AFTER_CONFIRMATION_DAYS', 2),
        'auto_update_delayed_status' => env('SUPPLY_LOGISTICS_AUTO_DELAY_STATUS', true),
    ],
    'health' => [
        'backup_marker_path' => env('SUPPLY_BACKUP_MARKER_PATH', storage_path('app/backups/last_successful_backup.txt')),
        'external_ai_allowed' => env('SUPPLY_EXTERNAL_AI_ALLOWED', false),
    ],
    'backup' => [
        'marker_path' => env('SUPPLY_BACKUP_MARKER_PATH', storage_path('app/backups/last_successful_backup.txt')),
        'max_age_hours' => env('SUPPLY_BACKUP_MAX_AGE_HOURS', 48),
    ],
    'production_readiness' => [
        'strict_by_default' => env('SUPPLY_PRODUCTION_READINESS_STRICT', false),
    ],
    'ai_boundary' => [
        'external_ai_allowed' => env('SUPPLY_EXTERNAL_AI_ALLOWED', false),
    ],
    'local_mode' => [
        'enabled' => env('SUPPLY_LOCAL_MODE', true),
    ],
    'integrations' => [
        'real_calls_enabled' => env('SUPPLY_REAL_INTEGRATION_CALLS_ENABLED', false),
        'require_approval_for_external' => true,
        'allow_auto_approve_local' => env('SUPPLY_ALLOW_AUTO_APPROVE_LOCAL_INTEGRATIONS', true),
    ],
    'email_providers' => [
        'gmail_enabled' => env('SUPPLY_GMAIL_ENABLED', false),
        'microsoft_graph_enabled' => env('SUPPLY_MICROSOFT_GRAPH_ENABLED', false),
        'imap_enabled' => env('SUPPLY_IMAP_ENABLED', false),
        'smtp_enabled' => env('SUPPLY_SMTP_ENABLED', false),
    ],
    'manufacturer_forms' => [
        'excel_enabled' => env('SUPPLY_MANUFACTURER_EXCEL_FORMS_ENABLED', true),
        'pdf_enabled' => env('SUPPLY_MANUFACTURER_PDF_FORMS_ENABLED', false),
        'max_upload_size_kb' => env('SUPPLY_MANUFACTURER_FORM_MAX_UPLOAD_SIZE_KB', 10240),
    ],
    'google_sheets' => [
        'enabled' => env('SUPPLY_GOOGLE_SHEETS_ENABLED', false),
    ],
    'external_ai' => [
        'enabled' => env('SUPPLY_EXTERNAL_AI_ENABLED', false),
        'require_redaction' => true,
    ],
    'pilot' => [
        'storage_path' => env('SUPPLY_PILOT_STORAGE_PATH', 'pilot'),
        'allow_real_email_send' => env('SUPPLY_PILOT_ALLOW_REAL_EMAIL_SEND', false),
        'allow_real_external_calls' => env('SUPPLY_PILOT_ALLOW_REAL_EXTERNAL_CALLS', false),
        'allow_external_ai' => env('SUPPLY_PILOT_ALLOW_EXTERNAL_AI', false),
        'max_upload_size_kb' => env('SUPPLY_PILOT_MAX_UPLOAD_SIZE_KB', 10240),
    ],
];
