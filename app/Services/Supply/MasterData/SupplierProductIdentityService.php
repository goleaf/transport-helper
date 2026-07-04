<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataAliasStatus;
use App\Models\Supplier;
use App\Models\SupplierProductIdentity;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class SupplierProductIdentityService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProductIdentityService $productIdentityService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{identity: SupplierProductIdentity, warnings: list<string>}
     */
    public function createMapping(array $validated, User $user): array
    {
        $this->requireReason($validated['reason'] ?? null);

        $identity = SupplierProductIdentity::query()->create($validated + [
            'status' => $this->userCanApprove($user) ? MasterDataAliasStatus::Active : MasterDataAliasStatus::Pending,
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $this->userCanApprove($user) ? $user->getKey() : null,
            'approved_at' => $this->userCanApprove($user) ? now() : null,
        ]);

        $this->auditLogService->write('supplier_product_identity_created', $identity, $user, null, [
            'supplier_id' => $identity->supplier_id,
            'product_id' => $identity->product_id,
            'supplier_sku' => $identity->supplier_sku,
            'status' => $identity->status?->value,
        ], [], $identity->company_id);

        return [
            'identity' => $identity,
            'warnings' => $identity->status === MasterDataAliasStatus::Pending ? ['mapping_pending_approval'] : [],
        ];
    }

    /**
     * @return array{identity: SupplierProductIdentity}
     */
    public function approveMapping(SupplierProductIdentity $identity, User $user, string $note): array
    {
        $this->requireReason($note);

        $old = $identity->getOriginal();
        $identity->forceFill([
            'status' => MasterDataAliasStatus::Active,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'reason' => $identity->reason ?: $note,
        ])->save();

        $this->auditLogService->write('supplier_product_identity_approved', $identity, $user, $old, $identity->getChanges(), [
            'approval_note' => $note,
        ], $identity->company_id);

        return ['identity' => $identity->refresh()];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function resolveSupplierProduct(Supplier $supplier, array $input): array
    {
        return $this->productIdentityService->resolve($supplier->company()->select(['id', 'name', 'code', 'timezone', 'default_currency'])->firstOrFail(), $input, $supplier);
    }

    /**
     * @return array{rule: SupplierProductRule, created: bool}
     */
    public function syncToSupplierProductRule(SupplierProductIdentity $identity, User $user): array
    {
        if ($identity->status !== MasterDataAliasStatus::Active) {
            throw new InvalidArgumentException('Only approved active supplier product identities can sync to supplier product rules.');
        }

        $rule = SupplierProductRule::query()->firstOrNew([
            'supplier_id' => $identity->supplier_id,
            'product_id' => $identity->product_id,
        ]);
        $created = ! $rule->exists;
        $rule->fill([
            'supplier_sku' => $identity->supplier_sku ?: $rule->supplier_sku,
            'order_enabled' => $rule->order_enabled ?? true,
        ]);
        $rule->save();

        $this->auditLogService->write('supplier_product_identity_approved', $identity, $user, null, [
            'supplier_product_rule_id' => $rule->id,
            'created' => $created,
        ], [], $identity->company_id);

        return ['rule' => $rule, 'created' => $created];
    }

    private function requireReason(mixed $reason): void
    {
        if (trim((string) $reason) === '') {
            throw new InvalidArgumentException('Reason is required.');
        }
    }

    private function userCanApprove(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermissionTo('manage_products');
    }
}
