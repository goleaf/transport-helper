<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataChangeRequestStatus;
use App\Models\MasterDataChangeRequest;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\SupplierProductIdentity;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MasterDataChangeRequestService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{request: MasterDataChangeRequest}
     */
    public function createRequest(array $validated, User $user): array
    {
        $this->requireReason($validated['reason'] ?? null);

        $request = MasterDataChangeRequest::query()->create($validated + [
            'status' => MasterDataChangeRequestStatus::Draft,
            'requested_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('master_data_change_request_created', $request, $user, null, [
            'request_type' => $request->request_type?->value,
            'status' => $request->status?->value,
        ], [], $request->company_id);

        return ['request' => $request];
    }

    /**
     * @return array{request: MasterDataChangeRequest}
     */
    public function submit(MasterDataChangeRequest $request, User $user): array
    {
        $old = $request->getOriginal();
        $request->forceFill(['status' => MasterDataChangeRequestStatus::PendingApproval])->save();

        $this->auditLogService->write('master_data_change_request_created', $request, $user, $old, $request->getChanges(), [
            'submitted' => true,
        ], $request->company_id);

        return ['request' => $request->refresh()];
    }

    /**
     * @return array{request: MasterDataChangeRequest}
     */
    public function approve(MasterDataChangeRequest $request, User $user, string $note): array
    {
        $this->requireReason($note);
        $old = $request->getOriginal();

        $request->forceFill([
            'status' => MasterDataChangeRequestStatus::Approved,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'approval_note' => $note,
        ])->save();

        $this->auditLogService->write('master_data_change_request_approved', $request, $user, $old, $request->getChanges(), [], $request->company_id);

        return ['request' => $request->refresh()];
    }

    /**
     * @return array{request: MasterDataChangeRequest}
     */
    public function reject(MasterDataChangeRequest $request, User $user, string $reason): array
    {
        $this->requireReason($reason);
        $old = $request->getOriginal();

        $request->forceFill([
            'status' => MasterDataChangeRequestStatus::Rejected,
            'rejected_by_user_id' => $user->getKey(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogService->write('master_data_change_request_rejected', $request, $user, $old, $request->getChanges(), [], $request->company_id);

        return ['request' => $request->refresh()];
    }

    /**
     * @return array{request: MasterDataChangeRequest, applied_model: Model|null}
     */
    public function apply(MasterDataChangeRequest $request, User $user): array
    {
        if ($request->status !== MasterDataChangeRequestStatus::Approved) {
            throw new InvalidArgumentException('Only approved master data change requests can be applied.');
        }

        $changes = $request->requested_changes_json ?? [];
        $model = match ($request->request_type?->value) {
            'create_product' => Product::query()->create($changes + ['company_id' => $request->company_id, 'is_active' => true]),
            'update_product' => $this->updateModel(Product::class, $request, $changes),
            'create_supplier' => Supplier::query()->create($changes + ['company_id' => $request->company_id, 'is_active' => true, 'type' => $changes['type'] ?? 'manufacturer']),
            'update_supplier' => $this->updateModel(Supplier::class, $request, $changes),
            'create_alias' => $this->createAlias($request, $changes, $user),
            'supplier_product_mapping' => SupplierProductIdentity::query()->create($changes + ['company_id' => $request->company_id, 'status' => 'active', 'created_by_user_id' => $user->getKey(), 'approved_by_user_id' => $user->getKey(), 'approved_at' => now()]),
            default => null,
        };

        $old = $request->getOriginal();
        $request->forceFill([
            'status' => MasterDataChangeRequestStatus::Applied,
            'applied_by_user_id' => $user->getKey(),
            'applied_at' => now(),
        ])->save();

        $this->auditLogService->write('master_data_change_request_applied', $request, $user, $old, $request->getChanges(), [
            'applied_model_type' => $model ? $model::class : null,
            'applied_model_id' => $model?->getKey(),
        ], $request->company_id);

        return ['request' => $request->refresh(), 'applied_model' => $model];
    }

    /**
     * @param  class-string<Model>  $class
     * @param  array<string, mixed>  $changes
     */
    private function updateModel(string $class, MasterDataChangeRequest $request, array $changes): ?Model
    {
        $model = $request->relatedModel;

        if (! $model instanceof $class) {
            return null;
        }

        $model->fill($changes);
        $model->save();

        return $model;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function createAlias(MasterDataChangeRequest $request, array $changes, User $user): ProductAlias|SupplierAlias
    {
        $aliasFor = $changes['alias_for'] ?? 'product';
        unset($changes['alias_for']);

        if ($aliasFor === 'supplier') {
            return SupplierAlias::query()->create($changes + [
                'company_id' => $request->company_id,
                'status' => 'active',
                'created_by_user_id' => $user->getKey(),
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => now(),
            ]);
        }

        return ProductAlias::query()->create($changes + [
            'company_id' => $request->company_id,
            'status' => 'active',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
        ]);
    }

    private function requireReason(mixed $reason): void
    {
        if (trim((string) $reason) === '') {
            throw new InvalidArgumentException('Reason is required.');
        }
    }
}
