<?php

namespace App\Services\Supply\MasterData;

use App\Enums\ProductLifecycleStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class ProductLifecycleService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{product: Product, warnings: list<string>}
     */
    public function changeStatus(Product $product, string $status, User $user, string $reason, array $options = []): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Lifecycle status change reason is required.');
        }

        $allowed = collect(ProductLifecycleStatus::cases())->map->value->all();
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid product lifecycle status.');
        }

        if ($status === ProductLifecycleStatus::Replaced->value && empty($options['replaced_by_product_id'])) {
            throw new InvalidArgumentException('Replacement product is required for replaced lifecycle status.');
        }

        $warnings = $this->openWorkflowWarnings($product);
        $old = $product->getOriginal();
        $product->forceFill([
            'lifecycle_status' => $status,
            'lifecycle_reason' => $reason,
            'replaced_by_product_id' => $options['replaced_by_product_id'] ?? $product->replaced_by_product_id,
            'is_active' => in_array($status, ['active', 'draft'], true),
        ])->save();

        $this->auditLogService->write('product_lifecycle_changed', $product, $user, $old, $product->getChanges(), [
            'warnings' => $warnings,
            'reason' => $reason,
        ], $product->company_id);

        return ['product' => $product->refresh(), 'warnings' => $warnings];
    }

    /**
     * @return list<string>
     */
    private function openWorkflowWarnings(Product $product): array
    {
        $warnings = [];

        if ($product->orderProposalItems()->whereIn('status', ['draft', 'needs_review', 'approved', 'adjusted'])->exists()) {
            $warnings[] = 'product_used_in_open_order_proposals';
        }

        if ($product->supplierOrderItems()->whereIn('status', ['draft', 'ordered', 'sent', 'confirmed', 'needs_review'])->exists()) {
            $warnings[] = 'product_used_in_open_supplier_orders';
        }

        return $warnings;
    }
}
