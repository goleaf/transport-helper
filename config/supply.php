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
    'forecasting' => [
        'seasonality' => [
            'min_factor' => env('SUPPLY_SEASONALITY_MIN_FACTOR', 0.50),
            'max_factor' => env('SUPPLY_SEASONALITY_MAX_FACTOR', 2.00),
            'minimum_history_months' => env('SUPPLY_SEASONALITY_MIN_HISTORY_MONTHS', 12),
        ],
        'outliers' => [
            'default_multiplier' => env('SUPPLY_OUTLIER_MULTIPLIER', 3.0),
        ],
        'scenarios' => [
            'allow_create_proposal_from_scenario' => env('SUPPLY_ALLOW_SCENARIO_TO_PROPOSAL', false),
        ],
    ],
    'procurement' => [
        'enabled' => env('SUPPLY_PROCUREMENT_CONTROLS_ENABLED', true),
        'default_enforcement_mode' => env('SUPPLY_PROCUREMENT_DEFAULT_ENFORCEMENT', 'advisory'),
        'allow_self_approval' => env('SUPPLY_PROCUREMENT_ALLOW_SELF_APPROVAL', false),
        'default_currency' => env('SUPPLY_PROCUREMENT_DEFAULT_CURRENCY', 'EUR'),
        'manual_currency_rates' => [
            'EUR' => 1.0,
        ],
        'block_missing_price_in_enforced_mode' => env('SUPPLY_PROCUREMENT_BLOCK_MISSING_PRICE', true),
    ],
    'master_data' => [
        'allow_auto_create_product_from_unknown_sku' => false,
        'allow_auto_merge' => false,
        'duplicate_detection' => [
            'name_similarity_threshold' => env('SUPPLY_MASTER_DATA_NAME_SIMILARITY_THRESHOLD', 0.85),
        ],
        'unknown_sku' => [
            'record_from_imports' => true,
            'record_from_ai_extractions' => true,
            'record_from_confirmations' => true,
        ],
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
    'incidents' => [
        'enabled' => env('SUPPLY_INCIDENTS_ENABLED', true),
        'auto_detection_enabled' => env('SUPPLY_INCIDENT_AUTO_DETECTION_ENABLED', true),
        'sla_monitor_enabled' => env('SUPPLY_INCIDENT_SLA_MONITOR_ENABLED', true),
        'dedupe_active_incidents' => true,
        'default_response_minutes' => [
            'critical' => 60,
            'high' => 240,
            'medium' => 1440,
            'low' => 4320,
        ],
        'default_resolution_minutes' => [
            'critical' => 480,
            'high' => 1440,
            'medium' => 4320,
            'low' => 14400,
        ],
        'detection_thresholds' => [
            'ai_review_overdue_hours' => env('SUPPLY_INCIDENT_AI_REVIEW_OVERDUE_HOURS', 24),
            'form_review_overdue_hours' => env('SUPPLY_INCIDENT_FORM_REVIEW_OVERDUE_HOURS', 24),
            'proposal_review_overdue_hours' => env('SUPPLY_INCIDENT_PROPOSAL_REVIEW_OVERDUE_HOURS', 48),
            'email_approval_overdue_hours' => env('SUPPLY_INCIDENT_EMAIL_APPROVAL_OVERDUE_HOURS', 24),
            'unknown_sku_overdue_hours' => env('SUPPLY_INCIDENT_UNKNOWN_SKU_OVERDUE_HOURS', 24),
        ],
    ],
];
