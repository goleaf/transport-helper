<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\Supplier;

class MasterDataAiExtractionHelperService
{
    public function __construct(
        private readonly ProductIdentityService $productIdentityService,
        private readonly UnknownSkuResolutionService $unknownSkuResolutionService,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     * @return array<string,mixed>
     */
    public function resolveProductForExtraction(Company $company, array $item, ?Supplier $supplier = null): array
    {
        $result = $this->productIdentityService->resolve($company, $item, $supplier);

        if (! $result['matched'] && (bool) config('supply.master_data.unknown_sku.record_from_ai_extractions', true)) {
            $this->recordUnknownSkuFromExtraction([
                'company_id' => $company->getKey(),
                'supplier_id' => $supplier?->getKey(),
                'unknown_sku' => $item['sku'] ?? $item['supplier_sku'] ?? null,
                'source_type' => 'ai_extraction',
                'source_reference' => $item['source_reference'] ?? null,
                'metadata_json' => ['item' => $item, 'requires_human_review' => true],
            ]);
        }

        return $result + ['ai_mapping_requires_human_approval' => true];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string,mixed>
     */
    public function recordUnknownSkuFromExtraction(array $context): array
    {
        return $this->unknownSkuResolutionService->recordUnknown($context + ['source_type' => 'ai_extraction']);
    }
}
