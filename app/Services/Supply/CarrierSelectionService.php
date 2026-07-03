<?php

namespace App\Services\Supply;

use App\Enums\CarrierQuoteStatus;
use App\Models\AuditLog;
use App\Models\CarrierQuote;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;

class CarrierSelectionService
{
    public function __construct(
        private readonly LogisticsRecordService $logisticsRecordService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function select(CarrierQuote $quote, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($quote, $user): array {
            $quote->loadMissing(['supplierOrder', 'carrier']);
            $oldQuoteValues = $quote->only(['status']);

            CarrierQuote::query()
                ->where('supplier_order_id', $quote->supplier_order_id)
                ->where('id', '!=', $quote->id)
                ->where('status', CarrierQuoteStatus::Selected)
                ->update(['status' => CarrierQuoteStatus::Rejected]);

            $quote->forceFill([
                'status' => CarrierQuoteStatus::Selected,
            ])->save();

            $logisticsResult = $this->logisticsRecordService->updateFromCarrierQuoteSelection($quote, $user);
            $logisticsRecord = $logisticsResult['record'];

            $this->auditLogService->logCarrierSelected(
                quote: $quote,
                user: $user,
                oldValues: [
                    'quote' => $oldQuoteValues,
                ],
                newValues: [
                    'quote' => $quote->only(['status']),
                    'logistics_record_id' => $logisticsRecord->id,
                ],
                metadata: [
                    'supplier_order_id' => $quote->supplier_order_id,
                    'confirmed_by_user' => true,
                ],
            );

            return [
                'quote' => $quote->refresh(),
                'logistics_record' => $logisticsRecord->refresh(),
            ];
        });
    }

    public function reject(CarrierQuote $quote, User $user): CarrierQuote
    {
        return DB::transaction(function () use ($quote, $user): CarrierQuote {
            $oldValues = $quote->only(['status']);

            $quote->forceFill([
                'status' => CarrierQuoteStatus::Rejected,
            ])->save();

            AuditLog::query()->create([
                'company_id' => $quote->company_id,
                'user_id' => $user->id,
                'event_type' => 'carrier_quote.rejected',
                'auditable_type' => $quote::class,
                'auditable_id' => $quote->id,
                'old_values_json' => $oldValues,
                'new_values_json' => $quote->only(['status']),
                'metadata_json' => [
                    'supplier_order_id' => $quote->supplier_order_id,
                ],
                'created_at' => now(),
            ]);

            return $quote->refresh();
        });
    }
}
