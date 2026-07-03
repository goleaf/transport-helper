<?php

namespace App\Services\Supply;

use App\Enums\CarrierQuoteStatus;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\SupplierOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarrierQuoteApplicationService
{
    public function __construct(
        private readonly CarrierQuoteScoringService $scoringService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function create(array $input): array
    {
        return DB::transaction(function () use ($input): array {
            $supplierOrder = SupplierOrder::query()
                ->with(['supplier:id,default_currency', 'logisticsRecords'])
                ->findOrFail((int) ($input['supplier_order_id'] ?? 0));
            $carrier = $this->carrier($supplierOrder, $input);
            $quoteData = $this->quoteData($input, $carrier, $supplierOrder);
            $scoring = $this->scoringService->score($quoteData, is_array($input['scoring_config'] ?? null) ? $input['scoring_config'] : []);
            $status = $scoring['requires_human_review'] ? CarrierQuoteStatus::NeedsReview : CarrierQuoteStatus::Received;

            $quote = CarrierQuote::query()->create([
                'company_id' => $supplierOrder->company_id,
                'supplier_order_id' => $supplierOrder->id,
                'carrier_id' => $carrier->id,
                'email_message_id' => $input['email_message_id'] ?? null,
                'price' => $quoteData['price'],
                'currency' => $quoteData['currency'],
                'pickup_date' => $quoteData['pickup_date'],
                'delivery_date' => $quoteData['delivery_date'],
                'transit_days' => $quoteData['transit_days'],
                'conditions' => $quoteData['conditions'],
                'reliability_score' => $quoteData['reliability_score'],
                'calculated_score' => $scoring['calculated_score'],
                'score_explanation_json' => array_merge($scoring['explanation'], [
                    'source_type' => $input['source_type'] ?? 'manual',
                    'requires_human_review' => $scoring['requires_human_review'],
                ]),
                'status' => $status,
                'created_from_ai_extraction_id' => $input['ai_email_extraction_id'] ?? null,
                'created_from_form_autofill_run_id' => $input['form_autofill_run_id'] ?? null,
            ]);

            $this->writeAuditLog('carrier_quote.created', $quote, $input['created_by_user_id'] ?? null, [], [
                'status' => $quote->status,
                'source_type' => $input['source_type'] ?? 'manual',
                'warnings' => $scoring['warnings'],
                'calculated_score' => $quote->calculated_score,
            ]);

            return [
                'quote' => $quote->load(['carrier', 'supplierOrder']),
                'scoring' => $scoring,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function carrier(SupplierOrder $supplierOrder, array $input): Carrier
    {
        if (isset($input['carrier_id'])) {
            return Carrier::query()
                ->where('company_id', $supplierOrder->company_id)
                ->findOrFail((int) $input['carrier_id']);
        }

        $carrierName = trim((string) ($input['carrier_name'] ?? ''));

        if ($carrierName === '') {
            throw ValidationException::withMessages([
                'carrier_name' => 'Carrier name is required when carrier_id is not provided.',
            ]);
        }

        return Carrier::query()->firstOrCreate([
            'company_id' => $supplierOrder->company_id,
            'name' => $carrierName,
        ], [
            'code' => null,
            'default_currency' => $input['currency'] ?? $supplierOrder->supplier?->default_currency ?? 'EUR',
            'reliability_score' => $input['reliability_score'] ?? null,
            'is_active' => true,
            'notes' => 'Created from carrier quote workflow.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function quoteData(array $input, Carrier $carrier, SupplierOrder $supplierOrder): array
    {
        $logisticsRecord = $supplierOrder->logisticsRecords->sortByDesc('id')->first();

        return [
            'price' => $input['price'] ?? null,
            'currency' => $input['currency'] ?? $carrier->default_currency ?? $supplierOrder->supplier?->default_currency ?? 'EUR',
            'pickup_date' => $input['pickup_date'] ?? null,
            'delivery_date' => $input['delivery_date'] ?? null,
            'transit_days' => $input['transit_days'] ?? null,
            'conditions' => $input['conditions'] ?? null,
            'reliability_score' => $input['reliability_score'] ?? $carrier->reliability_score,
            'required_pickup_date' => $input['required_pickup_date'] ?? $logisticsRecord?->pickup_date?->toDateString(),
            'required_delivery_date' => $input['required_delivery_date'] ?? $logisticsRecord?->delivery_date?->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(string $eventType, CarrierQuote $quote, mixed $userId, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'company_id' => $quote->company_id,
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'event_type' => $eventType,
            'auditable_type' => $quote::class,
            'auditable_id' => $quote->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [
                'supplier_order_id' => $quote->supplier_order_id,
            ],
            'created_at' => now(),
        ]);
    }
}
