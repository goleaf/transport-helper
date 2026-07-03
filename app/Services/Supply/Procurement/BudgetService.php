<?php

namespace App\Services\Supply\Procurement;

use App\Enums\BudgetStatus;
use App\Models\Company;
use App\Models\ProcurementBudget;
use App\Models\ProcurementBudgetLine;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class BudgetService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{budget: ProcurementBudget, warnings: list<string>}
     */
    public function createBudget(array $validated, User $user): array
    {
        $this->validateBudget($validated);
        $budget = ProcurementBudget::query()->create($validated + [
            'status' => $validated['status'] ?? BudgetStatus::Draft,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('procurement_budget_created', $budget, $user, null, [
            'name' => $budget->name,
            'date_from' => $budget->date_from?->toDateString(),
            'date_to' => $budget->date_to?->toDateString(),
            'total_amount' => $budget->total_amount,
        ], [], $budget->company_id);

        return ['budget' => $budget, 'warnings' => []];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{budget: ProcurementBudget, warnings: list<string>}
     */
    public function updateBudget(ProcurementBudget $budget, array $validated, User $user): array
    {
        $this->validateBudget(array_merge($budget->only(['date_from', 'date_to', 'total_amount']), $validated));
        $old = $budget->getOriginal();
        $budget->fill($validated);
        $budget->save();

        $this->auditLogService->write('procurement_budget_updated', $budget, $user, $old, $budget->getChanges(), [], $budget->company_id);

        return ['budget' => $budget->refresh(), 'warnings' => []];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{line: ProcurementBudgetLine, warnings: list<string>}
     */
    public function addLine(ProcurementBudget $budget, array $validated, User $user): array
    {
        if ((float) ($validated['amount'] ?? -1) < 0) {
            throw new InvalidArgumentException('Budget line amount must be zero or greater.');
        }

        $line = $budget->lines()->create($validated);
        $allocated = (float) $budget->lines()->sum('amount');
        $warnings = $allocated > (float) $budget->total_amount ? ['budget_lines_exceed_total_budget'] : [];

        $this->auditLogService->write('procurement_budget_line_created', $line, $user, null, [
            'budget_id' => $budget->getKey(),
            'amount' => $line->amount,
            'warnings' => $warnings,
        ], [], $budget->company_id);

        return ['line' => $line, 'warnings' => $warnings];
    }

    public function activeBudgetForDate(Company $company, string $date, ?string $currency = null): ?ProcurementBudget
    {
        return ProcurementBudget::query()
            ->select(['id', 'company_id', 'name', 'period_type', 'date_from', 'date_to', 'currency', 'total_amount', 'status', 'owner_user_id', 'notes', 'created_by_user_id'])
            ->with(['lines:id,procurement_budget_id,supplier_id,product_id,category,project_name,manager_name,amount,committed_amount,spent_amount,metadata_json'])
            ->active()
            ->where('company_id', $company->getKey())
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->when($currency !== null, fn ($query) => $query->where('currency', strtoupper($currency)))
            ->orderByDesc('date_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateBudget(array $payload): void
    {
        if (strtotime((string) ($payload['date_from'] ?? '')) > strtotime((string) ($payload['date_to'] ?? ''))) {
            throw new InvalidArgumentException('Budget start date must be before or equal to end date.');
        }

        if ((float) ($payload['total_amount'] ?? -1) < 0) {
            throw new InvalidArgumentException('Budget total amount must be zero or greater.');
        }
    }
}
