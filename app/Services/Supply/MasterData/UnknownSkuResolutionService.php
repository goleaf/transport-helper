<?php

namespace App\Services\Supply\MasterData;

use App\Enums\UnknownSkuResolutionStatus;
use App\Models\MasterDataChangeRequest;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\UnknownSkuResolution;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class UnknownSkuResolutionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly MasterDataChangeRequestService $changeRequestService,
        private readonly ProductIdentityService $productIdentityService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{resolution: UnknownSkuResolution, created: bool}
     */
    public function recordUnknown(array $data, ?User $user = null): array
    {
        $unknownSku = $this->productIdentityService->normalizeSku($data['unknown_sku'] ?? $data['sku'] ?? null);

        if ($unknownSku === null) {
            throw new InvalidArgumentException('Unknown SKU is required.');
        }

        $resolution = UnknownSkuResolution::query()->firstOrCreate([
            'company_id' => $data['company_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'unknown_sku' => $unknownSku,
            'source_type' => $data['source_type'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
        ], [
            'status' => UnknownSkuResolutionStatus::Unresolved,
            'metadata_json' => $data['metadata_json'] ?? [],
            'created_by_user_id' => $user?->getKey(),
        ]);

        $this->auditLogService->write('unknown_sku_recorded', $resolution, $user, null, [
            'unknown_sku' => $resolution->unknown_sku,
            'source_type' => $resolution->source_type,
            'created' => $resolution->wasRecentlyCreated,
        ], [], $resolution->company_id);

        return ['resolution' => $resolution, 'created' => $resolution->wasRecentlyCreated];
    }

    /**
     * @return array{resolution: UnknownSkuResolution}
     */
    public function resolveToProduct(UnknownSkuResolution $resolution, Product $product, User $user, string $reason): array
    {
        $this->requireReason($reason);

        $old = $resolution->getOriginal();
        $resolution->forceFill([
            'status' => UnknownSkuResolutionStatus::Mapped,
            'resolved_product_id' => $product->getKey(),
            'resolution_type' => 'existing_product',
            'reason' => $reason,
            'resolved_by_user_id' => $user->getKey(),
            'resolved_at' => now(),
        ])->save();

        $this->auditLogService->write('unknown_sku_resolved', $resolution, $user, $old, $resolution->getChanges(), [
            'product_id' => $product->getKey(),
            'resolution_type' => 'existing_product',
        ], $resolution->company_id);

        return ['resolution' => $resolution->refresh()];
    }

    /**
     * @return array{resolution: UnknownSkuResolution, alias: ProductAlias}
     */
    public function createAliasResolution(UnknownSkuResolution $resolution, Product $product, string $aliasType, User $user, string $reason): array
    {
        $this->requireReason($reason);

        $alias = ProductAlias::query()->create([
            'company_id' => $resolution->company_id,
            'product_id' => $product->getKey(),
            'alias' => $resolution->unknown_sku,
            'alias_type' => $aliasType,
            'source_type' => $resolution->source_type,
            'source_reference' => $resolution->source_reference,
            'status' => 'active',
            'confidence' => 1.0,
            'reason' => $reason,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->resolveToProduct($resolution, $product, $user, $reason);

        $this->auditLogService->write('product_alias_created', $alias, $user, null, [
            'unknown_sku_resolution_id' => $resolution->id,
        ], [], $alias->company_id);

        return ['resolution' => $resolution->refresh(), 'alias' => $alias];
    }

    /**
     * @param  array<string, mixed>  $requestedProductData
     * @return array{resolution: UnknownSkuResolution, change_request: MasterDataChangeRequest}
     */
    public function createProductChangeRequest(UnknownSkuResolution $resolution, array $requestedProductData, User $user, string $reason): array
    {
        $this->requireReason($reason);

        $result = $this->changeRequestService->createRequest([
            'company_id' => $resolution->company_id,
            'request_type' => 'create_product',
            'status' => 'pending_approval',
            'requested_changes_json' => $requestedProductData + ['unknown_sku_resolution_id' => $resolution->id],
            'reason' => $reason,
        ], $user);

        $old = $resolution->getOriginal();
        $resolution->forceFill([
            'status' => UnknownSkuResolutionStatus::ChangeRequested,
            'resolution_type' => 'product_change_request',
            'reason' => $reason,
            'resolved_by_user_id' => $user->getKey(),
            'resolved_at' => now(),
        ])->save();

        $this->auditLogService->write('unknown_sku_resolved', $resolution, $user, $old, $resolution->getChanges(), [
            'change_request_id' => $result['request']->id,
            'resolution_type' => 'product_change_request',
        ], $resolution->company_id);

        return ['resolution' => $resolution->refresh(), 'change_request' => $result['request']];
    }

    /**
     * @return array{resolution: UnknownSkuResolution}
     */
    public function ignore(UnknownSkuResolution $resolution, User $user, string $reason): array
    {
        $this->requireReason($reason);
        $old = $resolution->getOriginal();

        $resolution->forceFill([
            'status' => UnknownSkuResolutionStatus::Ignored,
            'resolution_type' => 'ignored',
            'reason' => $reason,
            'resolved_by_user_id' => $user->getKey(),
            'resolved_at' => now(),
        ])->save();

        $this->auditLogService->write('unknown_sku_ignored', $resolution, $user, $old, $resolution->getChanges(), [], $resolution->company_id);

        return ['resolution' => $resolution->refresh()];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count:int, rows:list<UnknownSkuResolution>}
     */
    public function unresolvedReport(array $filters = []): array
    {
        $rows = UnknownSkuResolution::query()
            ->select(['id', 'company_id', 'supplier_id', 'unknown_sku', 'source_type', 'source_reference', 'status', 'created_at'])
            ->with(['company:id,name', 'supplier:id,name'])
            ->unresolved()
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->latest('id')
            ->limit(500)
            ->get()
            ->all();

        return ['count' => count($rows), 'rows' => $rows];
    }

    private function requireReason(string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Reason is required.');
        }
    }
}
