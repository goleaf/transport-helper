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
use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\ImportBatch;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts procurement model fields to enums', function () {
    expect(Supplier::factory()->create(['type' => SupplierType::Manufacturer])->type)->toBe(SupplierType::Manufacturer)
        ->and(OrderProposal::factory()->create(['status' => OrderProposalStatus::NeedsReview])->status)->toBe(OrderProposalStatus::NeedsReview)
        ->and(OrderProposalItem::factory()->create(['status' => OrderProposalItemStatus::Adjusted])->status)->toBe(OrderProposalItemStatus::Adjusted)
        ->and(SupplierOrder::factory()->create(['status' => SupplierOrderStatus::EmailPrepared])->status)->toBe(SupplierOrderStatus::EmailPrepared)
        ->and(EmailAccount::factory()->create(['provider' => EmailProvider::Manual])->provider)->toBe(EmailProvider::Manual)
        ->and(EmailMessage::factory()->create(['direction' => EmailDirection::Outbound])->direction)->toBe(EmailDirection::Outbound)
        ->and(AiEmailExtraction::factory()->create(['prompt_version' => AiPromptVersion::EmailFormAutofillV1])->prompt_version)->toBe(AiPromptVersion::EmailFormAutofillV1)
        ->and(SupplierConfirmation::factory()->create(['status' => SupplierConfirmationStatus::DateMismatch])->status)->toBe(SupplierConfirmationStatus::DateMismatch)
        ->and(CarrierQuote::factory()->create(['status' => CarrierQuoteStatus::Selected])->status)->toBe(CarrierQuoteStatus::Selected)
        ->and(LogisticsRecord::factory()->create(['status' => LogisticsStatus::InTransit])->status)->toBe(LogisticsStatus::InTransit)
        ->and(ImportBatch::factory()->create(['status' => ImportBatchStatus::DryRun])->status)->toBe(ImportBatchStatus::DryRun)
        ->and(FormTemplate::factory()->create([
            'context_type' => FormTemplateContextType::LogisticsUpdate,
            'format_type' => FormTemplateFormatType::Json,
        ])->context_type)->toBe(FormTemplateContextType::LogisticsUpdate)
        ->and(FormAutofillRun::factory()->create(['status' => FormAutofillRunStatus::AiFilled])->status)->toBe(FormAutofillRunStatus::AiFilled)
        ->and(FormTemplateField::factory()->create(['field_type' => FormFieldType::Currency])->field_type)->toBe(FormFieldType::Currency);
});
