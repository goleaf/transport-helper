<?php

namespace App\Services\Supply\Procurement;

use App\Enums\ProcurementEnforcementMode;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class ProcurementGateService
{
    public function __construct(
        private readonly ProcurementComplianceService $complianceService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function gate(Model $model, string $action, User $user, array $options = []): array
    {
        $compliance = $this->complianceService->check($model, $options);
        $mode = (string) ($compliance['policy']['enforcement_mode'] ?? ProcurementEnforcementMode::Advisory->value);
        $warnings = $compliance['warnings'] ?? [];
        $blocking = [];
        $approvedExceptionTypes = $compliance['exceptions']['approved_types'] ?? [];
        $requiresApproval = (bool) ($compliance['approval_requirements']['requires_approval'] ?? false);
        $hasApproval = (bool) ($compliance['approval_state']['sufficient'] ?? false);

        if ($mode === ProcurementEnforcementMode::Enforced->value) {
            if ($requiresApproval && ! $hasApproval) {
                $blocking[] = 'procurement_approval_missing';
            }

            if (($compliance['budget_check']['over_budget_amount'] ?? 0) > 0 && ! in_array('budget_overrun', $approvedExceptionTypes, true)) {
                $blocking[] = 'budget_overrun';
            }

            if (($compliance['estimated_value']['missing_price_count'] ?? 0) > 0
                && (bool) ($compliance['policy']['rules']['block_missing_price_in_enforced_mode'] ?? true)
                && ! in_array('missing_price', $approvedExceptionTypes, true)) {
                $blocking[] = 'missing_price';
            }

            foreach (($compliance['supplier_rules']['blocking_reasons'] ?? []) as $reason) {
                if (! in_array($reason, $approvedExceptionTypes, true)) {
                    $blocking[] = $reason;
                }
            }
        }

        $blocking = array_values(array_unique($blocking));
        $status = $blocking !== []
            ? 'blocked'
            : ($warnings !== [] ? 'passed_with_warnings' : 'passed');
        $result = [
            'status' => $status,
            'action' => $action,
            'enforcement_mode' => $mode,
            'estimated_value' => $compliance['estimated_value'],
            'budget_check' => $compliance['budget_check'],
            'approval_requirements' => $compliance['approval_requirements'],
            'approval_state' => $compliance['approval_state'],
            'exceptions' => $compliance['exceptions'],
            'supplier_rules' => $compliance['supplier_rules'],
            'warnings' => $warnings,
            'errors' => [],
            'blocking_reasons' => $blocking,
        ];

        $this->auditLogService->write('procurement_gate_checked', $model, $user, null, [
            'action' => $action,
            'status' => $status,
            'blocking_reasons' => $blocking,
        ], [], $this->companyId($model));

        if ($status === 'blocked') {
            $this->auditLogService->write('procurement_gate_blocked', $model, $user, null, [
                'action' => $action,
                'blocking_reasons' => $blocking,
            ], [], $this->companyId($model));
        }

        if ($requiresApproval) {
            $this->auditLogService->write('procurement_approval_required', $model, $user, null, [
                'action' => $action,
                'requirements_count' => count($compliance['approval_requirements']['requirements'] ?? []),
            ], [], $this->companyId($model));
        }

        return $result;
    }

    private function companyId(Model $model): ?int
    {
        $companyId = $model->getAttribute('company_id');

        return is_numeric($companyId) ? (int) $companyId : null;
    }
}
