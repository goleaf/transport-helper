<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Services\AI\Email\AiEmailExtractionValidationService as EmailValidationService;

class AiEmailExtractionValidationService
{
    public function __construct(
        private readonly EmailValidationService $validationService,
    ) {}

    /**
     * @param  AiEmailExtraction|array<string, mixed>  $output
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function validate(AiEmailExtraction|array $output, array $context = []): array
    {
        if ($output instanceof AiEmailExtraction) {
            $output->loadMissing('emailMessage.relatedSupplierOrder.items.product', 'emailMessage.relatedSupplierOrder.items.product.supplierProductRules');
            $context = $this->contextFromExtraction($output);
            $result = $this->validationService->validate(is_array($output->output_json) ? $output->output_json : [], $context);

            return $result + [
                'reasons' => array_values(array_unique(array_merge($result['errors'], $result['warnings']))),
                'confidence' => (float) ($result['normalized_output']['confidence'] ?? 0),
                'output' => $result['normalized_output'],
            ];
        }

        return $this->validationService->validate($output, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function contextFromExtraction(AiEmailExtraction $extraction): array
    {
        $email = $extraction->emailMessage;
        $order = $email?->relatedSupplierOrder;

        return [
            'supplier' => $email?->relatedSupplier ? [
                'id' => $email->relatedSupplier->id,
                'name' => $email->relatedSupplier->name,
            ] : null,
            'supplier_order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ] : null,
            'expected_items' => $order?->items?->map(function ($item): array {
                $item->loadMissing('product.supplierProductRules');

                return [
                    'product_id' => $item->product_id,
                    'sku' => $item->product?->sku,
                    'manufacturer_sku' => $item->product?->manufacturer_sku,
                    'supplier_sku' => $item->product?->supplierProductRules?->first()?->supplier_sku,
                    'ordered_quantity' => (float) $item->ordered_quantity,
                ];
            })->values()->all() ?? [],
        ];
    }
}
