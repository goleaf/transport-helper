<?php

namespace App\Services\Supply\Procurement;

use App\Enums\ProcurementPolicyStatus;
use App\Models\Company;
use App\Models\ProcurementPolicy;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProcurementPolicyService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{policy: ProcurementPolicy}
     */
    public function createPolicy(array $validated, User $user): array
    {
        if (trim((string) ($validated['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Procurement policy name is required.');
        }

        return DB::transaction(function () use ($validated, $user): array {
            if ((bool) ($validated['is_default'] ?? false)) {
                $this->clearDefault((int) $validated['company_id']);
            }

            $policy = ProcurementPolicy::query()->create($validated + [
                'status' => ProcurementPolicyStatus::Active,
                'enforcement_mode' => config('supply.procurement.default_enforcement_mode', 'advisory'),
                'default_currency' => config('supply.procurement.default_currency', 'EUR'),
                'created_by_user_id' => $user->getKey(),
                'updated_by_user_id' => $user->getKey(),
            ]);

            $this->auditLogService->write('procurement_policy_created', $policy, $user, null, [
                'name' => $policy->name,
                'enforcement_mode' => $policy->enforcement_mode?->value ?? $policy->enforcement_mode,
                'is_default' => $policy->is_default,
            ], [], $policy->company_id);

            return ['policy' => $policy];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{policy: ProcurementPolicy}
     */
    public function updatePolicy(ProcurementPolicy $policy, array $validated, User $user): array
    {
        if (array_key_exists('name', $validated) && trim((string) $validated['name']) === '') {
            throw new InvalidArgumentException('Procurement policy name is required.');
        }

        return DB::transaction(function () use ($policy, $validated, $user): array {
            if ((bool) ($validated['is_default'] ?? false)) {
                $this->clearDefault((int) $policy->company_id, $policy);
            }

            $old = $policy->getOriginal();
            $policy->fill($validated + ['updated_by_user_id' => $user->getKey()]);
            $policy->save();

            $this->auditLogService->write('procurement_policy_updated', $policy, $user, $old, $policy->getChanges(), [], $policy->company_id);

            return ['policy' => $policy->refresh()];
        });
    }

    /**
     * @return array{policy: ProcurementPolicy}
     */
    public function archivePolicy(ProcurementPolicy $policy, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Archive reason is required.');
        }

        $old = $policy->getOriginal();
        $policy->forceFill([
            'status' => ProcurementPolicyStatus::Archived,
            'is_default' => false,
            'updated_by_user_id' => $user->getKey(),
        ])->save();

        $this->auditLogService->write('procurement_policy_archived', $policy, $user, $old, [
            'status' => ProcurementPolicyStatus::Archived->value,
            'reason' => $reason,
        ], [], $policy->company_id);

        return ['policy' => $policy->refresh()];
    }

    public function defaultPolicy(Company $company): ?ProcurementPolicy
    {
        return ProcurementPolicy::query()
            ->select([
                'id',
                'company_id',
                'name',
                'status',
                'enforcement_mode',
                'default_currency',
                'rules_json',
                'approval_thresholds_json',
                'supplier_rules_json',
                'budget_rules_json',
                'is_default',
                'created_by_user_id',
                'updated_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->active()
            ->where('company_id', $company->getKey())
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    private function clearDefault(int $companyId, ?ProcurementPolicy $except = null): void
    {
        ProcurementPolicy::query()
            ->where('company_id', $companyId)
            ->when($except instanceof ProcurementPolicy, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->update(['is_default' => false]);
    }
}
