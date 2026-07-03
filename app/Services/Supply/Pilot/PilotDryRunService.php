<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotRunStatus;
use App\Enums\PilotRunType;
use App\Enums\PilotSupplierStatus;
use App\Models\PilotRun;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class PilotDryRunService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function runImportDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::ImportDryRun, $user, ['import_preview_only']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runCalculationDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::CalculationDryRun, $user, ['calculation_uses_existing_deterministic_services_when_pilot_data_is_promoted']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runEmailDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::EmailDryRun, $user, ['real_email_send_blocked', 'log_sender_only']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runFormAutofillDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::FormAutofillDryRun, $user, ['rule_based_or_fake_extractor_only']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runConfirmationDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::ConfirmationDryRun, $user, ['confirmation_application_not_performed']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runTransportDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::TransportDryRun, $user, ['carrier_selection_not_performed']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runLogisticsDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::LogisticsDryRun, $user, ['receiving_not_persisted']);
    }

    /**
     * @return array<string, mixed>
     */
    public function runFullUatDryRun(PilotSupplier $pilot, User $user): array
    {
        return $this->run($pilot, PilotRunType::FullUatDryRun, $user, [
            'import_preview_only',
            'real_email_send_blocked',
            'external_api_calls_blocked',
            'external_ai_blocked',
            'carrier_selection_not_performed',
            'integration_activation_not_performed',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function runByType(PilotSupplier $pilot, string $runType, User $user): array
    {
        return match ($runType) {
            PilotRunType::ImportDryRun->value => $this->runImportDryRun($pilot, $user),
            PilotRunType::CalculationDryRun->value => $this->runCalculationDryRun($pilot, $user),
            PilotRunType::EmailDryRun->value => $this->runEmailDryRun($pilot, $user),
            PilotRunType::FormAutofillDryRun->value => $this->runFormAutofillDryRun($pilot, $user),
            PilotRunType::ConfirmationDryRun->value => $this->runConfirmationDryRun($pilot, $user),
            PilotRunType::TransportDryRun->value => $this->runTransportDryRun($pilot, $user),
            PilotRunType::LogisticsDryRun->value => $this->runLogisticsDryRun($pilot, $user),
            default => $this->runFullUatDryRun($pilot, $user),
        };
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function run(PilotSupplier $pilot, PilotRunType $runType, User $user, array $warnings = []): array
    {
        $this->auditLogService->write('pilot_dry_run_started', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'run_type' => $runType->value,
        ], $pilot->company_id);

        $result = [
            'run_type' => $runType->value,
            'status' => PilotRunStatus::PassedWithWarnings->value,
            'real_email_sent' => false,
            'external_api_called' => false,
            'external_ai_called' => false,
            'carrier_auto_selected' => false,
            'integrations_activated' => false,
            'domain_records_mutated' => false,
            'warnings' => $warnings,
            'summary' => 'Pilot dry-run completed in safe mode.',
        ];

        $run = PilotRun::query()->create([
            'pilot_supplier_id' => $pilot->id,
            'run_type' => $runType->value,
            'status' => PilotRunStatus::PassedWithWarnings->value,
            'started_by_user_id' => $user->id,
            'started_at' => now(),
            'finished_at' => now(),
            'result_json' => $result,
            'warnings_json' => $warnings,
            'errors_json' => [],
        ]);

        $pilot->update([
            'dry_run_result_json' => $result,
            'status' => PilotSupplierStatus::DryRunPassed->value,
        ]);

        $this->auditLogService->write('pilot_dry_run_completed', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'pilot_run_id' => $run->id,
            'run_type' => $runType->value,
            'real_email_sent' => false,
            'external_api_called' => false,
            'external_ai_called' => false,
            'carrier_auto_selected' => false,
        ], $pilot->company_id);

        return [
            'pilot' => $pilot->fresh(),
            'run' => $run,
            'result' => $result,
        ];
    }
}
