<?php

namespace App\Services\Supply\MasterData;

use App\Enums\SupplierLifecycleStatus;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class SupplierLifecycleService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{supplier: Supplier, warnings: list<string>}
     */
    public function changeStatus(Supplier $supplier, string $status, User $user, string $reason, array $options = []): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Lifecycle status change reason is required.');
        }

        $allowed = collect(SupplierLifecycleStatus::cases())->map->value->all();
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid supplier lifecycle status.');
        }

        $warnings = $this->openWorkflowWarnings($supplier);
        $old = $supplier->getOriginal();
        $supplier->forceFill([
            'lifecycle_status' => $status,
            'lifecycle_reason' => $reason,
            'merged_into_supplier_id' => $options['merged_into_supplier_id'] ?? $supplier->merged_into_supplier_id,
            'is_active' => in_array($status, ['active', 'draft'], true),
        ])->save();

        $this->auditLogService->write('supplier_lifecycle_changed', $supplier, $user, $old, $supplier->getChanges(), [
            'warnings' => $warnings,
            'reason' => $reason,
        ], $supplier->company_id);

        return ['supplier' => $supplier->refresh(), 'warnings' => $warnings];
    }

    /**
     * @return list<string>
     */
    private function openWorkflowWarnings(Supplier $supplier): array
    {
        $warnings = [];

        if ($supplier->orderProposals()->whereIn('status', ['draft', 'needs_review', 'approved'])->exists()) {
            $warnings[] = 'supplier_has_open_order_proposals';
        }

        if ($supplier->supplierOrders()->whereIn('status', ['draft', 'email_prepared', 'approved', 'sent', 'confirmed', 'needs_review'])->exists()) {
            $warnings[] = 'supplier_has_open_supplier_orders';
        }

        return $warnings;
    }
}
