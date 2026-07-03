<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\SupplierOrder;
use App\Services\Supply\CarrierQuoteApplicationService;
use Illuminate\Validation\ValidationException;

class CarrierQuoteFromAiExtractionService
{
    public function __construct(
        private readonly CarrierQuoteApplicationService $carrierQuoteApplicationService,
    ) {}

    public function create(AiEmailExtraction $extraction): CarrierQuote
    {
        $extraction->loadMissing('emailMessage.relatedSupplierOrder');
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $quote = is_array($output['carrier_quote'] ?? null) ? $output['carrier_quote'] : [];
        $supplierOrder = $this->supplierOrderFor($extraction, $output);

        if (! $supplierOrder instanceof SupplierOrder) {
            throw ValidationException::withMessages([
                'supplier_order' => 'Carrier quote requires a linked supplier order.',
            ]);
        }

        $carrier = $this->carrierFor($supplierOrder, $quote);
        $result = $this->carrierQuoteApplicationService->create([
            'supplier_order_id' => $supplierOrder->id,
            'carrier_id' => $carrier->id,
            'email_message_id' => $extraction->email_message_id,
            'price' => $quote['price'] ?? null,
            'currency' => $quote['currency'] ?? $carrier->default_currency,
            'pickup_date' => $quote['pickup_date'] ?? null,
            'delivery_date' => $quote['delivery_date'] ?? null,
            'transit_days' => $quote['transit_days'] ?? null,
            'conditions' => $quote['conditions'] ?? null,
            'reliability_score' => $carrier->reliability_score,
            'ai_email_extraction_id' => $extraction->id,
            'source_type' => 'ai_email_extraction',
        ]);

        return $result['quote'];
    }

    private function carrierFor(SupplierOrder $supplierOrder, array $quote): Carrier
    {
        $carrierName = (string) ($quote['carrier_name'] ?? 'Unknown Carrier');

        return Carrier::query()->firstOrCreate([
            'company_id' => $supplierOrder->company_id,
            'name' => $carrierName,
        ], [
            'code' => null,
            'default_currency' => $quote['currency'] ?? $supplierOrder->supplier?->default_currency ?? 'EUR',
            'reliability_score' => null,
            'is_active' => true,
            'notes' => 'Created from accepted AI email extraction.',
        ]);
    }

    private function supplierOrderFor(AiEmailExtraction $extraction, array $output): ?SupplierOrder
    {
        if ($extraction->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $extraction->emailMessage->relatedSupplierOrder;
        }

        $orderNumber = $output['supplier_order_number'] ?? null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return SupplierOrder::query()
            ->where('company_id', $extraction->emailMessage?->company_id)
            ->where('order_number', $orderNumber)
            ->first();
    }
}
