<?php

namespace Tests\Support;

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
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

class AnalyticsTestSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function fixture(): array
    {
        $company = Company::factory()->create(['name' => 'Analytics Demo Co']);
        $supplier = Supplier::factory()->for($company)->create(['name' => 'Reliable Components', 'default_lead_time_days' => 10]);
        $carrier = Carrier::factory()->for($company)->create(['name' => 'Fast Freight', 'reliability_score' => 92]);
        $user = User::factory()->create(['role' => 'admin']);

        SupplierContact::factory()->for($supplier)->create(['receives_orders' => true, 'is_active' => true]);
        CarrierContact::factory()->for($carrier)->create(['is_active' => true]);

        $product = Product::factory()->for($company)->create(['sku' => 'SKU-AN-001', 'name' => 'Analytics Widget']);
        SupplierProductRule::factory()->for($supplier)->for($product)->create([
            'pack_multiple' => 12,
            'moq' => 24,
            'lead_time_days' => 10,
            'safety_days' => 5,
            'order_enabled' => true,
        ]);

        StockSnapshot::factory()->for($company)->for($product)->create([
            'snapshot_date' => now()->subDay()->toDateString(),
            'free_stock' => 0,
            'reserved_quantity' => 0,
            'in_transit_quantity' => 20,
        ]);

        SalesHistory::factory()->for($company)->for($product)->create([
            'sales_date' => now()->subDays(4)->toDateString(),
            'quantity' => 20,
        ]);
        SalesHistory::factory()->for($company)->for($product)->create([
            'sales_date' => now()->subDays(2)->toDateString(),
            'quantity' => 10,
        ]);

        $proposal = OrderProposal::factory()->for($company)->for($supplier)->create([
            'status' => 'approved',
            'approved_at' => now()->subDays(6),
            'created_at' => now()->subDays(7),
        ]);
        $proposalItem = OrderProposalItem::factory()->for($proposal)->for($product)->create([
            'status' => 'adjusted',
            'recommended_quantity' => 120,
            'approved_quantity' => 96,
            'user_adjusted_quantity' => 96,
            'adjustment_reason' => 'Pilot demand correction',
            'requires_human_review' => true,
        ]);

        $supplierOrder = SupplierOrder::factory()->for($company)->for($supplier)->create([
            'order_proposal_id' => $proposal->id,
            'status' => 'sent',
            'order_number' => 'PO-AN-001',
            'order_date' => now()->subDays(8)->toDateString(),
            'sent_at' => now()->subDays(7),
            'email_approved_at' => now()->subDays(7)->subHours(2),
        ]);
        $supplierOrderItem = SupplierOrderItem::factory()->for($supplierOrder)->for($product)->create([
            'ordered_quantity' => 96,
            'confirmed_quantity' => 90,
            'received_quantity' => 80,
            'damaged_quantity' => 2,
            'status' => 'received_with_mismatch',
        ]);

        $email = EmailMessage::factory()->for($company)->for($supplier, 'relatedSupplier')->for($supplierOrder, 'relatedSupplierOrder')->create([
            'body_text' => 'Full private supplier body must not appear in analytics exports.',
            'received_at' => now()->subDays(6),
            'status' => 'received',
        ]);

        $extraction = AiEmailExtraction::factory()->for($email, 'emailMessage')->create([
            'confidence' => 70,
            'requires_human_review' => true,
            'accepted_at' => null,
            'rejected_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(5),
            'output_json' => ['unknown_sku_count' => 1, 'quantity_mismatch' => true],
        ]);

        $formRun = FormAutofillRun::factory()->for($company)->for($email, 'emailMessage')->for($extraction, 'aiEmailExtraction')->create([
            'status' => 'validated',
            'confidence' => 75,
            'created_at' => now()->subDays(6),
        ]);
        FormAutofillFieldValue::factory()->for($formRun, 'formAutofillRun')->create([
            'field_key' => 'quantity',
            'extracted_value' => ['value' => 96],
            'normalized_value' => ['value' => 96],
            'final_value' => ['value' => 90],
            'confidence' => 70,
            'requires_review' => true,
            'source_excerpt' => 'Confirming 90 pcs',
        ]);

        $confirmation = SupplierConfirmation::factory()->for($company)->for($supplierOrder)->for($email, 'emailMessage')->create([
            'status' => 'quantity_mismatch',
            'confirmation_date' => now()->subDays(5)->toDateString(),
            'ready_date' => now()->subDays(1)->toDateString(),
            'expected_arrival_date' => now()->addDays(2)->toDateString(),
            'discrepancies_json' => ['quantity_mismatch' => true],
            'created_from_ai_extraction_id' => $extraction->id,
            'created_from_form_autofill_run_id' => $formRun->id,
            'applied_by_user_id' => $user->id,
            'applied_at' => now()->subDays(5),
        ]);
        SupplierConfirmationItem::factory()->for($confirmation, 'supplierConfirmation')->for($product)->create([
            'ordered_quantity' => 96,
            'confirmed_quantity' => 90,
            'discrepancy_quantity' => -6,
            'status' => 'quantity_mismatch',
            'discrepancy_type' => 'quantity_mismatch',
        ]);

        $selectedQuote = CarrierQuote::factory()->for($company)->for($supplierOrder)->for($carrier)->for($email, 'emailMessage')->create([
            'price' => 180,
            'pickup_date' => now()->addDay()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'calculated_score' => 92,
            'status' => 'selected',
            'created_from_ai_extraction_id' => null,
            'selected_by_user_id' => $user->id,
            'selected_at' => now()->subDays(4),
        ]);
        CarrierQuote::factory()->for($company)->for($supplierOrder)->for($carrier)->for($email, 'emailMessage')->create([
            'price' => 100,
            'pickup_date' => now()->addDays(6)->toDateString(),
            'delivery_date' => now()->addDays(12)->toDateString(),
            'calculated_score' => 60,
            'status' => 'received',
            'created_from_ai_extraction_id' => null,
            'warnings_json' => ['late_delivery'],
        ]);

        $logisticsRecord = LogisticsRecord::factory()->for($company)->for($supplier)->for($supplierOrder)->for($carrier)->create([
            'supplier_confirmation_id' => $confirmation->id,
            'selected_carrier_quote_id' => $selectedQuote->id,
            'order_date' => now()->subDays(8)->toDateString(),
            'confirmation_date' => now()->subDays(5)->toDateString(),
            'ready_date' => now()->subDays(1)->toDateString(),
            'pickup_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'actual_received_date' => null,
            'status' => 'delayed',
            'receiving_discrepancies_json' => ['received_less' => true],
        ]);

        $importBatch = ImportBatch::factory()->for($company)->create([
            'import_type' => 'sales_history',
            'status' => 'completed_with_errors',
            'total_rows' => 10,
            'successful_rows' => 8,
            'failed_rows' => 2,
            'started_at' => now()->subDays(3),
            'finished_at' => now()->subDays(3)->addMinute(),
        ]);
        ImportRow::factory()->for($importBatch)->create(['status' => 'failed', 'error_message' => 'unknown SKU ABC']);

        AuditLog::factory()->for($company)->for($user)->create(['event_type' => 'order_quantity_adjusted']);
        AuditLog::factory()->for($company)->for($user)->create(['event_type' => 'carrier_selected']);

        return compact(
            'company',
            'supplier',
            'carrier',
            'user',
            'product',
            'proposal',
            'proposalItem',
            'supplierOrder',
            'supplierOrderItem',
            'email',
            'extraction',
            'formRun',
            'confirmation',
            'selectedQuote',
            'logisticsRecord',
            'importBatch',
        );
    }
}
