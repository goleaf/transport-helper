<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\DataStewardAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class DataStewardService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{assignment: DataStewardAssignment}
     */
    public function assign(array $validated, User $user): array
    {
        $assignment = DataStewardAssignment::query()->create($validated + [
            'is_active' => true,
            'assigned_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('data_steward_assigned', $assignment, $user, null, [
            'stewardship_type' => $assignment->stewardship_type,
            'user_id' => $assignment->user_id,
        ], [], $assignment->company_id);

        return ['assignment' => $assignment];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<DataStewardAssignment>
     */
    public function activeAssignments(Company $company, array $filters = []): array
    {
        return DataStewardAssignment::query()
            ->select(['id', 'company_id', 'user_id', 'stewardship_type', 'supplier_id', 'product_id', 'category', 'is_active', 'notes', 'assigned_by_user_id', 'created_at'])
            ->with(['user:id,name,email', 'supplier:id,name', 'product:id,sku,name', 'assignedBy:id,name'])
            ->whereBelongsTo($company)
            ->active()
            ->when(isset($filters['stewardship_type']), fn ($query) => $query->where('stewardship_type', $filters['stewardship_type']))
            ->latest('id')
            ->limit(500)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<DataStewardAssignment>
     */
    public function resolveStewards(string $type, array $context = []): array
    {
        return DataStewardAssignment::query()
            ->select(['id', 'company_id', 'user_id', 'stewardship_type', 'supplier_id', 'product_id', 'category', 'is_active'])
            ->with(['user:id,name,email'])
            ->active()
            ->where('company_id', $context['company_id'] ?? null)
            ->where(function ($query) use ($type, $context): void {
                $query->where('stewardship_type', $type)
                    ->orWhere('stewardship_type', 'company_products');

                if (isset($context['supplier_id'])) {
                    $query->orWhere('supplier_id', $context['supplier_id']);
                }

                if (isset($context['product_id'])) {
                    $query->orWhere('product_id', $context['product_id']);
                }

                if (isset($context['category'])) {
                    $query->orWhere('category', $context['category']);
                }
            })
            ->get()
            ->all();
    }
}
