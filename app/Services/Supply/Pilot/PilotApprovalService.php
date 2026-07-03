<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotSupplierStatus;
use App\Enums\UserRole;
use App\Models\IntegrationConnection;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class PilotApprovalService
{
    public function __construct(
        private readonly PilotUatChecklistService $uatChecklistService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function approveForUat(PilotSupplier $pilot, User $user, string $note): array
    {
        $this->authorizeApprover($user);
        $this->requireNote($note);

        $errors = $pilot->readiness_result_json['errors'] ?? null;

        if (! is_array($errors) || $errors !== []) {
            throw ValidationException::withMessages([
                'readiness' => 'Pilot readiness must have no errors before UAT approval.',
            ]);
        }

        $pilot->update([
            'status' => PilotSupplierStatus::ReadyForUat->value,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        $this->auditLogService->write('pilot_approved_for_uat', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'note' => $note,
        ], $pilot->company_id);

        return ['pilot' => $pilot->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function approveForLive(PilotSupplier $pilot, User $user, string $note): array
    {
        $this->authorizeApprover($user);
        $this->requireNote($note);

        $evaluation = $this->uatChecklistService->evaluate($pilot);

        if (! $evaluation['live_ready']) {
            throw ValidationException::withMessages([
                'uat_checklist' => 'Critical UAT checklist items must pass before live approval.',
            ]);
        }

        $activeIntegrationsBefore = IntegrationConnection::query()
            ->where('company_id', $pilot->company_id)
            ->where('is_active', true)
            ->count();

        $pilot->update([
            'status' => PilotSupplierStatus::ApprovedForLive->value,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        $activeIntegrationsAfter = IntegrationConnection::query()
            ->where('company_id', $pilot->company_id)
            ->where('is_active', true)
            ->count();

        $this->auditLogService->write('pilot_approved_for_live', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'note' => $note,
            'integration_activation_changed' => $activeIntegrationsBefore !== $activeIntegrationsAfter,
        ], $pilot->company_id);

        return ['pilot' => $pilot->fresh(), 'integration_activation_changed' => $activeIntegrationsBefore !== $activeIntegrationsAfter];
    }

    /**
     * @return array<string, mixed>
     */
    public function block(PilotSupplier $pilot, User $user, string $reason): array
    {
        $this->authorizeApprover($user);
        $this->requireNote($reason, 'Block reason is required.');

        $oldStatus = $pilot->status;
        $pilot->update(['status' => PilotSupplierStatus::Blocked->value]);

        $this->auditLogService->write('pilot_blocked', $pilot, $user, ['status' => $oldStatus], [
            'status' => PilotSupplierStatus::Blocked->value,
        ], [
            'pilot_supplier_id' => $pilot->id,
            'reason' => $reason,
        ], $pilot->company_id);

        return ['pilot' => $pilot->fresh()];
    }

    private function authorizeApprover(User $user): void
    {
        if (
            ! $user->hasRole(UserRole::Admin)
            && ! $user->hasPermissionTo('manage_settings')
            && ! $user->hasPermissionTo('manage_integrations')
        ) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not allowed to approve pilots.',
            ]);
        }
    }

    private function requireNote(string $note, string $message = 'Approval note is required.'): void
    {
        if (trim($note) === '') {
            throw ValidationException::withMessages([
                'note' => $message,
            ]);
        }
    }
}
