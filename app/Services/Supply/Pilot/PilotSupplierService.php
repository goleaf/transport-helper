<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotSupplierStatus;
use App\Enums\UserRole;
use App\Models\PilotSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class PilotSupplierService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function create(array $validated, User $user): array
    {
        $this->authorizeConfigurator($user);

        $supplier = Supplier::query()
            ->select(['id', 'company_id', 'name'])
            ->findOrFail((int) $validated['supplier_id']);

        if ((int) $supplier->company_id !== (int) $validated['company_id']) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Selected supplier does not belong to the selected company.',
            ]);
        }

        if (! (bool) ($validated['allow_multiple'] ?? false)) {
            $existing = PilotSupplier::query()
                ->activeForSupplier($supplier->id)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'This supplier already has an active pilot.',
                ]);
            }
        }

        $pilot = PilotSupplier::query()->create([
            'company_id' => $validated['company_id'],
            'supplier_id' => $supplier->id,
            'name' => $validated['name'],
            'status' => PilotSupplierStatus::Draft->value,
            'description' => $validated['description'] ?? null,
            'data_sources_json' => [],
            'import_mappings_json' => [],
            'manufacturer_form_mapping_json' => [],
            'email_sample_mapping_json' => [],
            'carrier_mapping_json' => [],
            'logistics_mapping_json' => [],
            'uat_checklist_json' => [],
            'created_by_user_id' => $user->id,
        ]);

        $this->auditLogService->write('pilot_supplier_created', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'supplier_id' => $supplier->id,
            'status' => $pilot->status,
        ], $pilot->company_id);

        return ['pilot' => $pilot];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function update(PilotSupplier $pilot, array $validated, User $user): array
    {
        $this->authorizeConfigurator($user);

        $oldValues = $pilot->only(['name', 'status', 'description', 'data_sources_json']);
        $pilot->update(array_intersect_key($validated, array_flip([
            'name',
            'status',
            'description',
            'data_sources_json',
        ])));

        $this->auditLogService->write('pilot_supplier_updated', $pilot, $user, $oldValues, $pilot->fresh()->only([
            'name',
            'status',
            'description',
            'data_sources_json',
        ]), [
            'pilot_supplier_id' => $pilot->id,
        ], $pilot->company_id);

        return ['pilot' => $pilot->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function markConfiguring(PilotSupplier $pilot, User $user): array
    {
        return $this->update($pilot, ['status' => PilotSupplierStatus::Configuring->value], $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function archive(PilotSupplier $pilot, User $user, string $reason): array
    {
        $this->authorizeConfigurator($user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Archive reason is required.',
            ]);
        }

        $oldStatus = $pilot->status;
        $pilot->update(['status' => PilotSupplierStatus::Archived->value]);

        $this->auditLogService->write('pilot_supplier_archived', $pilot, $user, ['status' => $oldStatus], [
            'status' => PilotSupplierStatus::Archived->value,
        ], [
            'pilot_supplier_id' => $pilot->id,
            'reason' => $reason,
        ], $pilot->company_id);

        return ['pilot' => $pilot->fresh()];
    }

    private function authorizeConfigurator(User $user): void
    {
        if (
            ! $user->hasRole(UserRole::Admin)
            && ! $user->hasRole(UserRole::SupplyManager)
            && ! $user->hasPermissionTo('manage_settings')
            && ! $user->hasPermissionTo('manage_integrations')
        ) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not allowed to manage pilot suppliers.',
            ]);
        }
    }
}
