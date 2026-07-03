<?php

namespace App\Services\Supply\Integrations;

use App\Enums\IntegrationApprovalStatus;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Models\IntegrationConnection;

class IntegrationAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $checks = [];

        $activeConnections = IntegrationConnection::query()
            ->select(['id', 'name', 'provider', 'status', 'approval_status', 'last_test_status', 'is_active', 'encrypted_config'])
            ->where('is_active', true)
            ->get();

        foreach ($activeConnections as $connection) {
            $checks[] = [
                'name' => 'integration_'.$connection->id.'_approved',
                'status' => $connection->approval_status === IntegrationApprovalStatus::Approved->value ? 'ok' : 'error',
                'message' => $connection->name.' approval status is '.$connection->approval_status.'.',
            ];
            $checks[] = [
                'name' => 'integration_'.$connection->id.'_tested',
                'status' => in_array($connection->last_test_status, [IntegrationTestStatus::Success->value, 'passed'], true) ? 'ok' : 'warning',
                'message' => $connection->name.' last test status is '.($connection->last_test_status ?: 'not_tested').'.',
            ];
            $checks[] = [
                'name' => 'integration_'.$connection->id.'_credentials',
                'status' => empty($connection->encrypted_config) ? 'error' : 'ok',
                'message' => $connection->name.' credentials are '.(empty($connection->encrypted_config) ? 'missing.' : 'configured.'),
            ];
        }

        $externalAiActive = IntegrationConnection::query()
            ->where('provider', IntegrationProvider::ExternalAi->value)
            ->where('status', IntegrationStatus::Active->value)
            ->where('is_active', true)
            ->exists();

        if ($externalAiActive && ! (bool) config('supply.external_ai.enabled', false)) {
            $checks[] = [
                'name' => 'external_ai_config',
                'status' => 'error',
                'message' => 'External AI integration is active while external AI config is disabled.',
            ];
        }

        if ($checks === []) {
            $checks[] = [
                'name' => 'integrations',
                'status' => 'ok',
                'message' => 'No active external integrations require attention.',
            ];
        }

        $status = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')
            ? 'error'
            : (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning') ? 'warning' : 'ok');

        return [
            'status' => $status,
            'checks' => $checks,
        ];
    }
}
