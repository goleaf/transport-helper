<?php

use App\Enums\AiPromptVersion;
use App\Enums\CarrierQuoteStatus;
use App\Enums\EmailDirection;
use App\Enums\EmailProvider;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormFieldType;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\ImportBatchStatus;
use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\SupplierType;

it('defines procurement enum backing values', function () {
    expect(array_column(SupplierType::cases(), 'value'))->toBe([
        'manufacturer',
        'distributor',
        'carrier',
        'mixed',
    ])
        ->and(array_column(OrderProposalStatus::cases(), 'value'))->toBe([
            'draft',
            'needs_review',
            'approved',
            'rejected',
            'converted_to_supplier_order',
        ])
        ->and(array_column(OrderProposalItemStatus::cases(), 'value'))->toBe([
            'draft',
            'needs_review',
            'approved',
            'adjusted',
            'rejected',
        ])
        ->and(array_column(SupplierOrderStatus::cases(), 'value'))->toBe([
            'draft',
            'awaiting_approval',
            'approved',
            'email_prepared',
            'sent',
            'confirmed',
            'partially_confirmed',
            'delayed',
            'completed',
            'cancelled',
            'needs_review',
        ])
        ->and(array_column(EmailDirection::cases(), 'value'))->toBe(['inbound', 'outbound'])
        ->and(array_column(EmailProvider::cases(), 'value'))->toBe([
            'gmail',
            'microsoft_graph',
            'imap_smtp',
            'manual',
        ])
        ->and(array_column(AiPromptVersion::cases(), 'value'))->toBe([
            'supplier_email_parser_v1',
            'supplier_reply_draft_v1',
            'carrier_quote_parser_v1',
            'email_form_autofill_v1',
        ])
        ->and(array_column(SupplierConfirmationStatus::cases(), 'value'))->toBe([
            'draft',
            'confirmed',
            'partially_confirmed',
            'quantity_mismatch',
            'date_mismatch',
            'needs_review',
            'rejected',
        ])
        ->and(array_column(CarrierQuoteStatus::cases(), 'value'))->toBe([
            'received',
            'needs_review',
            'selected',
            'rejected',
        ])
        ->and(array_column(LogisticsStatus::cases(), 'value'))->toBe([
            'planned',
            'order_sent',
            'confirmed',
            'waiting_for_ready_date',
            'ready_for_pickup',
            'pickup_scheduled',
            'in_transit',
            'delayed',
            'arrived',
            'completed',
            'cancelled',
            'needs_review',
        ])
        ->and(array_column(ImportBatchStatus::cases(), 'value'))->toBe([
            'draft',
            'dry_run',
            'processing',
            'completed',
            'completed_with_errors',
            'failed',
            'rolled_back',
        ])
        ->and(array_column(FormTemplateContextType::cases(), 'value'))->toBe([
            'supplier_order',
            'supplier_confirmation',
            'ready_date_update',
            'quantity_mismatch',
            'carrier_quote',
            'logistics_update',
            'custom_email_form',
        ])
        ->and(array_column(FormTemplateFormatType::cases(), 'value'))->toBe([
            'internal_html',
            'excel',
            'csv',
            'pdf',
            'json',
            'portal_manual',
            'portal_automation_placeholder',
        ])
        ->and(array_column(FormAutofillRunStatus::cases(), 'value'))->toBe([
            'draft',
            'ai_filled',
            'needs_review',
            'validated',
            'applied',
            'rejected',
            'exported',
            'failed',
        ])
        ->and(array_column(FormFieldType::cases(), 'value'))->toBe([
            'text',
            'number',
            'decimal',
            'date',
            'currency',
            'sku',
            'email',
            'select',
            'textarea',
            'boolean',
        ]);
});
