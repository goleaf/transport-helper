<?php

namespace App\Services\Supply\Integrations;

use App\Enums\IntegrationApprovalStatus;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IntegrationApprovalService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function submitForApproval(IntegrationConnection $connection, User $user, ?string $reason = null): array
    {
        $this->authorize($user);

        $connection->update([
            'status' => IntegrationStatus::PendingApproval->value,
            'approval_status' => IntegrationApprovalStatus::Pending->value,
            'is_active' => false,
        ]);

        $this->audit('integration_submitted_for_approval', $connection, $user, ['reason' => $reason]);

        return ['connection' => $connection->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function approve(IntegrationConnection $connection, User $user, ?string $reason = null): array
    {
        $this->authorize($user);

        $connection->update([
            'status' => IntegrationStatus::Approved->value,
            'approval_status' => IntegrationApprovalStatus::Approved->value,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
            'is_active' => false,
        ]);

        $this->audit('integration_approved', $connection, $user, ['reason' => $reason]);

        return ['connection' => $connection->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(IntegrationConnection $connection, User $user, string $reason): array
    {
        $this->authorize($user);

        $connection->update([
            'status' => IntegrationStatus::Failed->value,
            'approval_status' => IntegrationApprovalStatus::Rejected->value,
            'is_active' => false,
            'notes' => trim(($connection->notes ? $connection->notes.PHP_EOL : '').'Rejected: '.$reason),
        ]);

        $this->audit('integration_rejected', $connection, $user, ['reason' => $reason]);

        return ['connection' => $connection->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function revoke(IntegrationConnection $connection, User $user, string $reason): array
    {
        $this->authorize($user);

        $connection->update([
            'status' => IntegrationStatus::Revoked->value,
            'approval_status' => IntegrationApprovalStatus::Revoked->value,
            'is_active' => false,
            'notes' => trim(($connection->notes ? $connection->notes.PHP_EOL : '').'Revoked: '.$reason),
        ]);

        $this->audit('integration_revoked', $connection, $user, ['reason' => $reason]);

        return ['connection' => $connection->fresh()];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function activate(IntegrationConnection $connection, User $user, array $options = []): array
    {
        $this->authorize($user);

        if ($connection->requires_approval && $connection->approval_status !== IntegrationApprovalStatus::Approved->value) {
            throw ValidationException::withMessages([
                'approval_status' => 'Integration must be approved before activation.',
            ]);
        }

        if (($connection->last_test_status !== IntegrationTestStatus::Success->value) && ! ($options['override_activation'] ?? false)) {
            throw ValidationException::withMessages([
                'last_test_status' => 'Integration must pass a connection test before activation.',
            ]);
        }

        if (empty($connection->encrypted_config)) {
            throw ValidationException::withMessages([
                'encrypted_config' => 'Integration credentials must be configured before activation.',
            ]);
        }

        $connection->update([
            'status' => IntegrationStatus::Active->value,
            'is_active' => true,
        ]);

        $this->audit('integration_activated', $connection, $user, [
            'override_activation' => (bool) ($options['override_activation'] ?? false),
        ]);

        return ['connection' => $connection->fresh()];
    }

    private function authorize(User $user): void
    {
        if (! $user->hasRole('admin') && ! $user->hasPermissionTo('manage_integrations')) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not allowed to approve integrations.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(string $event, IntegrationConnection $connection, User $user, array $metadata = []): void
    {
        $this->auditLogService->write($event, $connection, $user, null, null, $metadata + [
            'integration_connection_id' => $connection->id,
            'provider' => $connection->provider,
            'status' => $connection->status,
            'approval_status' => $connection->approval_status,
        ], $connection->company_id);
    }
}
