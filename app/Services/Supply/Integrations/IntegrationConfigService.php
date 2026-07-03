<?php

namespace App\Services\Supply\Integrations;

use App\Enums\IntegrationApprovalStatus;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IntegrationConfigService
{
    public function __construct(
        private readonly IntegrationCredentialService $credentials,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function createConnection(array $validated, User $user): array
    {
        $this->authorize($user);

        $config = $validated['config'] ?? $validated['encrypted_config'] ?? [];
        $isExternal = (bool) ($validated['is_external'] ?? $this->isExternalProvider((string) $validated['provider']));
        $requiresApproval = (bool) ($validated['requires_approval'] ?? ($isExternal || config('supply.integrations.require_approval_for_external', true)));
        $canAutoApprove = ! $isExternal && (bool) config('supply.integrations.allow_auto_approve_local', true);

        $connection = IntegrationConnection::query()->create([
            'company_id' => $validated['company_id'],
            'type' => $validated['type'],
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'environment' => $validated['environment'] ?? 'test',
            'encrypted_config' => is_array($config) ? $config : [],
            'is_external' => $isExternal,
            'requires_approval' => $requiresApproval,
            'status' => IntegrationStatus::Configured->value,
            'approval_status' => $canAutoApprove ? IntegrationApprovalStatus::Approved->value : IntegrationApprovalStatus::Pending->value,
            'is_active' => false,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditLogService->write('integration_config_created', $connection, $user, null, null, [
            'provider' => $connection->provider,
            'type' => $this->scalar($connection->type),
            'status' => $connection->status,
            'approval_status' => $connection->approval_status,
            'masked_config' => $this->credentials->maskConfig($connection->encrypted_config ?? []),
        ], $connection->company_id);

        return [
            'connection' => $connection,
            'masked_config' => $this->credentials->maskConfig($connection->encrypted_config ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function updateConnection(IntegrationConnection $connection, array $validated, User $user): array
    {
        $this->authorize($user);

        $oldValues = $connection->only(['name', 'provider', 'environment', 'status', 'approval_status', 'is_active', 'is_external', 'requires_approval']);
        $config = $validated['config'] ?? $validated['encrypted_config'] ?? null;
        $data = array_intersect_key($validated, array_flip([
            'company_id',
            'type',
            'provider',
            'name',
            'environment',
            'is_external',
            'requires_approval',
            'notes',
        ]));

        if (is_array($config)) {
            $data['encrypted_config'] = $config;
        }

        $data['status'] = IntegrationStatus::Configured->value;
        $connection->update($data);

        $this->auditLogService->write('integration_config_updated', $connection, $user, $oldValues, [
            'name' => $connection->name,
            'provider' => $connection->provider,
            'environment' => $connection->environment,
            'status' => $connection->status,
            'approval_status' => $connection->approval_status,
            'is_active' => $connection->is_active,
            'is_external' => $connection->is_external,
            'requires_approval' => $connection->requires_approval,
        ], [
            'masked_config' => $this->credentials->maskConfig($connection->encrypted_config ?? []),
        ], $connection->company_id);

        return [
            'connection' => $connection->fresh(),
            'masked_config' => $this->credentials->maskConfig($connection->encrypted_config ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function disableConnection(IntegrationConnection $connection, User $user, string $reason): array
    {
        $this->authorize($user);

        $oldStatus = $connection->status;
        $connection->update([
            'status' => IntegrationStatus::Disabled->value,
            'is_active' => false,
            'notes' => trim(($connection->notes ? $connection->notes.PHP_EOL : '').'Disabled: '.$reason),
        ]);

        $this->auditLogService->write('integration_disabled', $connection, $user, ['status' => $oldStatus], [
            'status' => IntegrationStatus::Disabled->value,
        ], [
            'reason' => $reason,
        ], $connection->company_id);

        return ['connection' => $connection->fresh()];
    }

    private function authorize(User $user): void
    {
        if (! $user->hasRole('admin') && ! $user->hasPermissionTo('manage_integrations')) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not allowed to manage integrations.',
            ]);
        }
    }

    private function isExternalProvider(string $provider): bool
    {
        return ! in_array($provider, [
            IntegrationProvider::Manual->value,
            IntegrationProvider::LocalLlm->value,
        ], true);
    }

    private function scalar(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
