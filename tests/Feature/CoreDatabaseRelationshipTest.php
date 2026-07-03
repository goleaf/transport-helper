<?php

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\IntegrationConnection;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('connects the core supply database models through relationships', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $supplier = Supplier::factory()->for($company)->create(['type' => 'manufacturer']);
    $contact = SupplierContact::factory()->for($supplier)->create();
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-REL-1']);
    $rule = SupplierProductRule::factory()->for($supplier)->for($product)->create();
    $stockSnapshot = StockSnapshot::factory()->for($company)->for($product)->create();
    $salesHistory = SalesHistory::factory()->for($company)->for($product)->create();
    $reservation = Reservation::factory()->for($company)->for($product)->create();
    $inboundOrder = InboundOrder::factory()->for($company)->for($supplier)->create();
    $inboundItem = InboundOrderItem::factory()->for($inboundOrder)->for($product)->create();
    $calculationRun = CalculationRun::factory()->for($company)->for($supplier)->for($user, 'startedBy')->create();
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($calculationRun)
        ->for($user, 'createdBy')
        ->create();
    $proposalItem = OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($product)->create();
    $supplierOrder = SupplierOrder::factory()->for($company)->for($supplier)->for($proposal, 'orderProposal')->create();
    $supplierOrderItem = SupplierOrderItem::factory()->for($supplierOrder)->for($product)->create();
    $emailAccount = EmailAccount::factory()->for($company)->create();
    $emailMessage = EmailMessage::factory()
        ->for($company)
        ->for($emailAccount, 'emailAccount')
        ->for($supplier, 'relatedSupplier')
        ->for($supplierOrder, 'relatedSupplierOrder')
        ->create();
    $aiExtraction = AiEmailExtraction::factory()->for($emailMessage)->for($user, 'reviewedBy')->create();
    $carrier = Carrier::factory()->for($company)->create();
    $template = FormTemplate::factory()->for($company)->for($supplier)->for($carrier)->create();
    $templateField = FormTemplateField::factory()->for($template, 'formTemplate')->create();
    $autofillRun = FormAutofillRun::factory()
        ->for($company)
        ->for($emailMessage)
        ->for($template, 'formTemplate')
        ->for($aiExtraction, 'aiEmailExtraction')
        ->for($user, 'createdBy')
        ->create();
    $autofillField = FormAutofillFieldValue::factory()->for($autofillRun, 'formAutofillRun')->create();
    $confirmation = SupplierConfirmation::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($emailMessage)
        ->for($aiExtraction, 'aiEmailExtraction')
        ->for($autofillRun, 'formAutofillRun')
        ->create();
    $confirmationItem = SupplierConfirmationItem::factory()->for($confirmation, 'supplierConfirmation')->for($product)->create();
    $quote = CarrierQuote::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($carrier)
        ->for($emailMessage)
        ->for($aiExtraction, 'aiEmailExtraction')
        ->for($autofillRun, 'formAutofillRun')
        ->create();
    $logisticsRecord = LogisticsRecord::factory()->for($company)->for($supplierOrder)->for($supplier)->for($carrier)->create();
    $importBatch = ImportBatch::factory()->for($company)->for($user, 'startedBy')->create();
    $importRow = ImportRow::factory()->for($importBatch, 'importBatch')->create([
        'related_model_type' => Product::class,
        'related_model_id' => $product->getKey(),
    ]);
    $exportFile = ExportFile::factory()->for($company)->for($user, 'createdBy')->create([
        'related_model_type' => SupplierOrder::class,
        'related_model_id' => $supplierOrder->getKey(),
    ]);
    $integration = IntegrationConnection::factory()->for($company)->create();
    $auditLog = AuditLog::factory()->for($company)->for($user)->create([
        'auditable_type' => SupplierOrder::class,
        'auditable_id' => $supplierOrder->getKey(),
    ]);

    expect($supplier->company->is($company))->toBeTrue()
        ->and($supplier->contacts->contains($contact))->toBeTrue()
        ->and($product->company->is($company))->toBeTrue()
        ->and($rule->supplier->is($supplier))->toBeTrue()
        ->and($rule->product->is($product))->toBeTrue()
        ->and($stockSnapshot->product->is($product))->toBeTrue()
        ->and($salesHistory->product->is($product))->toBeTrue()
        ->and($reservation->product->is($product))->toBeTrue()
        ->and($inboundOrder->items->contains($inboundItem))->toBeTrue()
        ->and($calculationRun->orderProposals->contains($proposal))->toBeTrue()
        ->and($proposal->items->contains($proposalItem))->toBeTrue()
        ->and($supplierOrder->items->contains($supplierOrderItem))->toBeTrue()
        ->and($emailMessage->aiEmailExtractions->contains($aiExtraction))->toBeTrue()
        ->and($template->fields->contains($templateField))->toBeTrue()
        ->and($autofillRun->fieldValues->contains($autofillField))->toBeTrue()
        ->and($confirmation->items->contains($confirmationItem))->toBeTrue()
        ->and($quote->carrier->is($carrier))->toBeTrue()
        ->and($logisticsRecord->supplierOrder->is($supplierOrder))->toBeTrue()
        ->and($importBatch->rows->contains($importRow))->toBeTrue()
        ->and($exportFile->company->is($company))->toBeTrue()
        ->and($integration->company->is($company))->toBeTrue()
        ->and($auditLog->auditable->is($supplierOrder))->toBeTrue();
});
