<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotRunStatus;
use App\Enums\PilotRunType;
use App\Enums\PilotSupplierStatus;
use App\Models\PilotRun;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class PilotReadinessService
{
    public function __construct(
        private readonly PilotDataQualityService $dataQualityService,
        private readonly PilotMappingService $mappingService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function check(PilotSupplier $pilot, ?User $user = null): array
    {
        $dataQuality = $this->dataQualityService->analyze($pilot);
        $mapping = $this->mappingService->validateMappings($pilot->fresh());
        $errors = array_values(array_unique(array_merge($dataQuality['errors'], $mapping['errors'])));
        $warnings = array_values(array_unique(array_merge($dataQuality['warnings'], $mapping['warnings'])));
        $status = $errors !== []
            ? PilotRunStatus::Failed->value
            : ($warnings !== [] ? PilotRunStatus::PassedWithWarnings->value : PilotRunStatus::Passed->value);

        $result = [
            'status' => $status,
            'data_quality' => $dataQuality,
            'mapping' => $mapping,
            'warnings' => $warnings,
            'errors' => $errors,
            'integration_checks' => [
                'status' => 'warning',
                'message' => 'Real integrations remain disabled unless separately approved.',
            ],
        ];

        $run = PilotRun::query()->create([
            'pilot_supplier_id' => $pilot->id,
            'run_type' => PilotRunType::ReadinessCheck->value,
            'status' => $status,
            'started_by_user_id' => $user?->id,
            'started_at' => now(),
            'finished_at' => now(),
            'result_json' => $result,
            'warnings_json' => $warnings,
            'errors_json' => $errors,
        ]);

        $pilot->update([
            'readiness_result_json' => $result,
            'status' => $errors === [] ? PilotSupplierStatus::ReadyForDryRun->value : PilotSupplierStatus::Blocked->value,
        ]);

        $this->auditLogService->write('pilot_readiness_checked', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'pilot_run_id' => $run->id,
            'status' => $status,
            'warning_count' => count($warnings),
            'error_count' => count($errors),
        ], $pilot->company_id);

        return [
            'pilot' => $pilot->fresh(),
            'run' => $run,
            'result' => $result,
        ];
    }
}
