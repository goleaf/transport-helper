<?php

namespace App\Services\Supply\Confirmations;

use App\Models\SupplierOrder;
use App\Models\User;

class SupplierConfirmationManualDataService
{
    public function __construct(
        private readonly SupplierConfirmationSourceNormalizer $sourceNormalizer,
        private readonly SupplierConfirmationApplicationService $applicationService,
    ) {}

    /**
     * @param  array<string, mixed>  $manualData
     * @return array<string, mixed>
     */
    public function applyManual(SupplierOrder $order, array $manualData, User $user): array
    {
        return $this->applicationService->apply(
            $order,
            $this->sourceNormalizer->fromManual($manualData),
            $user,
            [
                'update_inbound' => (bool) ($manualData['update_inbound'] ?? true),
                'update_logistics' => (bool) ($manualData['update_logistics'] ?? true),
                'allow_over_confirmation' => (bool) ($manualData['allow_over_confirmation'] ?? false),
                'allow_missing_items' => (bool) ($manualData['allow_missing_items'] ?? true),
            ],
        );
    }
}
