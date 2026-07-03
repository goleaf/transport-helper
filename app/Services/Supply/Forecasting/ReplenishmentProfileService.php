<?php

namespace App\Services\Supply\Forecasting;

use App\Enums\ReplenishmentProfileStatus;
use App\Models\Company;
use App\Models\ReplenishmentProfile;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class ReplenishmentProfileService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{profile: ReplenishmentProfile}
     */
    public function createProfile(array $validated, User $user): array
    {
        if (trim((string) ($validated['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Replenishment profile name is required.');
        }

        $profile = ReplenishmentProfile::query()->create($validated + [
            'status' => ReplenishmentProfileStatus::Active,
            'priority' => $validated['priority'] ?? 100,
            'created_by_user_id' => $user->getKey(),
            'updated_by_user_id' => $user->getKey(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->auditLogService->write('replenishment_profile_created', $profile, $user, null, [
            'name' => $profile->name,
            'priority' => $profile->priority,
            'scope' => $this->scope($profile),
        ], [], $profile->company_id);

        return ['profile' => $profile];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{profile: ReplenishmentProfile}
     */
    public function updateProfile(ReplenishmentProfile $profile, array $validated, User $user): array
    {
        if (array_key_exists('name', $validated) && trim((string) $validated['name']) === '') {
            throw new InvalidArgumentException('Replenishment profile name is required.');
        }

        $old = $profile->getOriginal();
        $profile->fill($validated + ['updated_by_user_id' => $user->getKey()]);
        $profile->save();

        $this->auditLogService->write('replenishment_profile_updated', $profile, $user, $old, $profile->getChanges(), [
            'scope' => $this->scope($profile),
        ], $profile->company_id);

        return ['profile' => $profile->refresh()];
    }

    /**
     * @return array{profile: ReplenishmentProfile}
     */
    public function archiveProfile(ReplenishmentProfile $profile, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Archive reason is required.');
        }

        $old = $profile->getOriginal();
        $profile->forceFill([
            'status' => ReplenishmentProfileStatus::Archived,
            'is_active' => false,
            'updated_by_user_id' => $user->getKey(),
            'notes' => trim((string) $profile->notes."\nArchived reason: ".$reason),
        ])->save();

        $this->auditLogService->write('replenishment_profile_archived', $profile, $user, $old, [
            'status' => $profile->status->value,
            'is_active' => $profile->is_active,
            'reason' => $reason,
        ], [], $profile->company_id);

        return ['profile' => $profile->refresh()];
    }

    /**
     * @return list<ReplenishmentProfile>
     */
    public function activeProfiles(Company $company): array
    {
        return ReplenishmentProfile::query()
            ->select([
                'id',
                'company_id',
                'supplier_id',
                'product_id',
                'category',
                'name',
                'status',
                'priority',
                'lead_time_days_override',
                'safety_days_override',
                'safety_stock_multiplier',
                'seasonality_enabled',
                'seasonality_mode',
                'exclude_promotions',
                'exclude_anomalies',
                'outlier_detection_enabled',
                'outlier_multiplier',
                'reservation_strategy',
                'pallet_strategy',
                'transport_strategy',
                'strategic_minimum_order_enabled',
                'config_json',
                'notes',
                'is_active',
                'created_by_user_id',
                'updated_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->activeForCompany($company)
            ->orderBy('priority')
            ->orderByDesc('id')
            ->limit(1000)
            ->get()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function scope(ReplenishmentProfile $profile): array
    {
        return [
            'supplier_id' => $profile->supplier_id,
            'product_id' => $profile->product_id,
            'category' => $profile->category,
        ];
    }
}
