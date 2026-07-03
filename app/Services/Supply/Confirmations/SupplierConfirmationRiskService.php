<?php

namespace App\Services\Supply\Confirmations;

use App\Events\SupplierConfirmationRiskChanged;
use App\Models\SupplierConfirmation;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class SupplierConfirmationRiskService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $discrepancyResult
     * @return array<string, mixed>
     */
    public function handleRisk(SupplierConfirmation $confirmation, array $discrepancyResult, ?User $user = null): array
    {
        $riskReasons = collect($discrepancyResult['discrepancies'] ?? [])
            ->pluck('type')
            ->filter(fn (mixed $type): bool => is_string($type) && in_array($type, [
                'quantity_lower_than_ordered',
                'quantity_higher_than_ordered',
                'missing_item',
                'delayed_ready_date',
                'delayed_arrival_date',
                'unknown_sku',
            ], true))
            ->unique()
            ->values()
            ->all();

        if ($riskReasons !== []) {
            SupplierConfirmationRiskChanged::dispatch($confirmation, $riskReasons);
            $this->auditLogService->write('supplier_confirmation_risk_flagged', $confirmation, $user, null, null, [
                'supplier_confirmation_id' => $confirmation->getKey(),
                'supplier_order_id' => $confirmation->supplier_order_id,
                'risk_reasons' => $riskReasons,
            ], $confirmation->company_id);
        }

        return [
            'risk_flagged' => $riskReasons !== [],
            'risk_reasons' => $riskReasons,
            'notifications' => [],
            'notification_behavior' => 'No dedicated notification classes are created in this task; risk is exposed through audit and event dispatch.',
        ];
    }
}
