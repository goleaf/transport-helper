<?php

namespace App\Services\Supply\Integrations;

use App\Enums\IntegrationApprovalStatus;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationTestStatus;
use App\Exceptions\NotConfiguredYetException;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IntegrationConnectionTestService
{
    public function __construct(
        private readonly IntegrationCredentialService $credentials,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function test(IntegrationConnection $connection, array $options = [], ?User $user = null): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $allowRealCall = (bool) ($options['allow_real_call'] ?? false);

        if (! $dryRun) {
            $this->guardRealCall($connection, $allowRealCall);
        }

        $result = $dryRun
            ? $this->dryRunResult($connection)
            : $this->realCallResult($connection);

        $connection->update([
            'last_tested_at' => now(),
            'last_test_status' => $result['status'],
            'last_test_result_json' => $result,
        ]);

        $this->auditLogService->write('integration_connection_tested', $connection, $user, null, null, [
            'integration_connection_id' => $connection->id,
            'provider' => $connection->provider,
            'dry_run' => $dryRun,
            'status' => $result['status'],
            'result' => $result,
        ], $connection->company_id);

        return $result + ['connection' => $connection->fresh()];
    }

    private function guardRealCall(IntegrationConnection $connection, bool $allowRealCall): void
    {
        if (! $allowRealCall) {
            throw ValidationException::withMessages([
                'allow_real_call' => 'A real integration test requires explicit allow_real_call.',
            ]);
        }

        if (app()->environment('testing')) {
            throw ValidationException::withMessages([
                'environment' => 'Real integration calls are blocked in the testing environment.',
            ]);
        }

        if (! (bool) config('supply.integrations.real_calls_enabled', false)) {
            throw ValidationException::withMessages([
                'real_calls_enabled' => 'Real integration calls are disabled by configuration.',
            ]);
        }

        if ($connection->requires_approval && $connection->approval_status !== IntegrationApprovalStatus::Approved->value) {
            throw ValidationException::withMessages([
                'approval_status' => 'Integration must be approved before a real test.',
            ]);
        }

        if (empty($connection->encrypted_config)) {
            throw ValidationException::withMessages([
                'encrypted_config' => 'Integration credentials must be configured before testing.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dryRunResult(IntegrationConnection $connection): array
    {
        $config = $connection->encrypted_config ?? [];
        $provider = (string) $connection->provider;

        if (in_array($provider, [IntegrationProvider::Manual->value, IntegrationProvider::LocalLlm->value], true)) {
            return [
                'status' => IntegrationTestStatus::Success->value,
                'dry_run' => true,
                'provider' => $provider,
                'message' => 'Manual/local provider configuration is test-safe.',
                'masked_config' => $this->credentials->maskConfig($config),
                'real_call_performed' => false,
                'warnings' => [],
            ];
        }

        return [
            'status' => IntegrationTestStatus::Warning->value,
            'dry_run' => true,
            'provider' => $provider,
            'message' => 'Dry-run validated configuration shape; no external provider was contacted.',
            'masked_config' => $this->credentials->maskConfig($config),
            'real_call_performed' => false,
            'warnings' => ['real_call_not_performed'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realCallResult(IntegrationConnection $connection): array
    {
        throw NotConfiguredYetException::forAdapter((string) $connection->provider);
    }
}
