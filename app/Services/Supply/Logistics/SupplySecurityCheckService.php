<?php

namespace App\Services\Supply\Logistics;

use App\Enums\HealthCheckStatus;
use App\Models\AppSetting;
use App\Models\EmailAccount;
use App\Models\IntegrationConnection;

class SupplySecurityCheckService
{
    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [
            $this->check('app_key', filled(config('app.key')) ? HealthCheckStatus::Ok : HealthCheckStatus::Error, filled(config('app.key')) ? 'Application key is set.' : 'Application key is missing.'),
            $this->check('app_debug_production', app()->isProduction() && config('app.debug') ? HealthCheckStatus::Warning : HealthCheckStatus::Ok, app()->isProduction() && config('app.debug') ? 'APP_DEBUG is enabled in production.' : 'Debug mode is acceptable for current environment.'),
            $this->check('external_ai_allowed', config('supply.health.external_ai_allowed') ? HealthCheckStatus::Warning : HealthCheckStatus::Ok, config('supply.health.external_ai_allowed') ? 'External AI is allowed by config.' : 'External AI is disabled by default.'),
            $this->countCheck('email_accounts_missing_encrypted_config', EmailAccount::query()->where('is_active', true)->whereNotIn('provider', ['manual', 'log'])->whereNull('encrypted_config')->count()),
            $this->countCheck('integrations_missing_encrypted_config', IntegrationConnection::query()->where('is_active', true)->where('is_external', true)->whereNull('encrypted_config')->count()),
            $this->suspiciousAppSettingsCheck(),
            $this->check('private_storage_paths', is_dir(storage_path('app')) && ! is_link(storage_path('app')) ? HealthCheckStatus::Ok : HealthCheckStatus::Warning, 'Private storage path checked.'),
            $this->noDtoCheck(),
        ];

        return [
            'status' => collect($checks)->contains(fn (array $check): bool => $check['status'] === HealthCheckStatus::Error->value)
                ? HealthCheckStatus::Error->value
                : (collect($checks)->contains(fn (array $check): bool => $check['status'] === HealthCheckStatus::Warning->value) ? HealthCheckStatus::Warning->value : HealthCheckStatus::Ok->value),
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function suspiciousAppSettingsCheck(): array
    {
        $keys = AppSetting::query()
            ->where(function ($query): void {
                $query->where('key', 'like', '%token%')
                    ->orWhere('key', 'like', '%password%')
                    ->orWhere('key', 'like', '%secret%');
            })
            ->pluck('key')
            ->all();

        return $this->check('suspicious_app_settings_keys', $keys === [] ? HealthCheckStatus::Ok : HealthCheckStatus::Warning, $keys === [] ? 'No suspicious app setting keys found.' : 'Suspicious app setting keys found; values are hidden.', [
            'keys' => $keys,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function noDtoCheck(): array
    {
        return $this->check('no_dto_app_data', is_dir(app_path('Data')) ? HealthCheckStatus::Error : HealthCheckStatus::Ok, 'No app/Data directory check completed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function countCheck(string $name, int $count): array
    {
        return $this->check($name, $count > 0 ? HealthCheckStatus::Warning : HealthCheckStatus::Ok, "{$count} issue(s) found.", ['count' => $count]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function check(string $name, HealthCheckStatus $status, string $message, array $metadata = []): array
    {
        return [
            'name' => $name,
            'status' => $status->value,
            'message' => $message,
            'metadata' => $metadata,
        ];
    }
}
