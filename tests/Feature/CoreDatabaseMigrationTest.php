<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates all supply agent core database tables', function () {
    $tables = [
        'companies',
        'suppliers',
        'supplier_contacts',
        'products',
        'supplier_product_rules',
        'stock_snapshots',
        'sales_history',
        'inbound_orders',
        'inbound_order_items',
        'reservations',
        'calculation_runs',
        'order_proposals',
        'order_proposal_items',
        'supplier_orders',
        'supplier_order_items',
        'email_accounts',
        'email_messages',
        'email_attachments',
        'ai_email_extractions',
        'form_templates',
        'form_template_fields',
        'form_autofill_runs',
        'form_autofill_field_values',
        'form_autofill_outputs',
        'supplier_confirmations',
        'supplier_confirmation_items',
        'carriers',
        'carrier_contacts',
        'carrier_quotes',
        'logistics_records',
        'import_batches',
        'import_rows',
        'export_files',
        'integration_connections',
        'app_settings',
        'audit_logs',
        'roles',
        'permissions',
        'permission_role',
        'role_user',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }
});

it('creates core stage one columns needed by later workflows', function () {
    expect(Schema::hasColumns('products', ['company_id', 'sku', 'manufacturer_sku', 'name']))->toBeTrue()
        ->and(Schema::hasColumns('order_proposal_items', ['raw_need', 'recommended_quantity', 'explanation_json', 'warnings_json']))->toBeTrue()
        ->and(Schema::hasColumns('supplier_orders', ['email_subject', 'email_body', 'email_approved_at', 'email_approved_by_user_id', 'no_attachment_confirmed']))->toBeTrue()
        ->and(Schema::hasColumns('email_messages', ['body_text', 'body_html', 'raw_headers_json']))->toBeTrue()
        ->and(Schema::hasColumns('form_autofill_field_values', ['source_excerpt', 'extracted_value', 'normalized_value', 'final_value']))->toBeTrue()
        ->and(Schema::hasColumns('carrier_quotes', ['calculated_score', 'score_explanation_json']))->toBeTrue()
        ->and(Schema::hasColumns('logistics_records', ['delivery_date', 'transport_price', 'status']))->toBeTrue()
        ->and(Schema::hasColumns('audit_logs', ['event_type', 'auditable_type', 'auditable_id']))->toBeTrue()
        ->and(Schema::hasColumns('roles', ['name', 'label']))->toBeTrue()
        ->and(Schema::hasColumns('permissions', ['name', 'label']))->toBeTrue();
});
