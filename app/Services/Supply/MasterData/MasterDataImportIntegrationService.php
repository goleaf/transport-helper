<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\Supplier;

class MasterDataImportIntegrationService
{
    public function __construct(
        private readonly ProductIdentityService $productIdentityService,
        private readonly UnknownSkuResolutionService $unknownSkuResolutionService,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array<string,mixed>
     */
    public function resolveProductForImport(Company $company, array $row, ?Supplier $supplier = null): array
    {
        $result = $this->productIdentityService->resolve($company, $row, $supplier);

        if (! $result['matched'] && (bool) config('supply.master_data.unknown_sku.record_from_imports', true)) {
            $this->recordUnknownSkuFromImport([
                'company_id' => $company->getKey(),
                'supplier_id' => $supplier?->getKey(),
                'unknown_sku' => $row['sku'] ?? $row['supplier_sku'] ?? null,
                'source_type' => 'import',
                'source_reference' => $row['source_reference'] ?? null,
                'metadata_json' => ['row' => $row],
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string,mixed>
     */
    public function recordUnknownSkuFromImport(array $context): array
    {
        return $this->unknownSkuResolutionService->recordUnknown($context + ['source_type' => 'import']);
    }
}
