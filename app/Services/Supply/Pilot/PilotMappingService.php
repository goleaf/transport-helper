<?php

namespace App\Services\Supply\Pilot;

use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class PilotMappingService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveImportMapping(PilotSupplier $pilot, string $importType, array $mapping, User $user): array
    {
        $this->assertPilotFile($pilot, $mapping);

        $mappings = $pilot->import_mappings_json ?? [];
        $mappings[$importType] = $mapping;
        $pilot->update(['import_mappings_json' => $mappings]);

        $this->audit($pilot, $user, 'import', ['import_type' => $importType]);

        return ['pilot' => $pilot->fresh(), 'validation' => $this->validateMappings($pilot->fresh())];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveManufacturerFormMapping(PilotSupplier $pilot, array $mapping, User $user): array
    {
        $pilot->update(['manufacturer_form_mapping_json' => $mapping]);
        $this->audit($pilot, $user, 'manufacturer_form');

        return ['pilot' => $pilot->fresh(), 'validation' => $this->validateMappings($pilot->fresh())];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveEmailSampleMapping(PilotSupplier $pilot, string $sampleType, array $mapping, User $user): array
    {
        $mappings = $pilot->email_sample_mapping_json ?? [];
        $mappings[$sampleType] = $mapping;
        $pilot->update(['email_sample_mapping_json' => $mappings]);
        $this->audit($pilot, $user, 'email_sample', ['sample_type' => $sampleType]);

        return ['pilot' => $pilot->fresh(), 'validation' => $this->validateMappings($pilot->fresh())];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveCarrierMapping(PilotSupplier $pilot, array $mapping, User $user): array
    {
        $pilot->update(['carrier_mapping_json' => $mapping]);
        $this->audit($pilot, $user, 'carrier_quote');

        return ['pilot' => $pilot->fresh(), 'validation' => $this->validateMappings($pilot->fresh())];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveLogisticsMapping(PilotSupplier $pilot, array $mapping, User $user): array
    {
        $pilot->update(['logistics_mapping_json' => $mapping]);
        $this->audit($pilot, $user, 'logistics');

        return ['pilot' => $pilot->fresh(), 'validation' => $this->validateMappings($pilot->fresh())];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateMappings(PilotSupplier $pilot): array
    {
        $errors = [];
        $warnings = [];
        $importMappings = $pilot->import_mappings_json ?? [];

        foreach (['sales_history_sample', 'stock_snapshot_sample'] as $requiredImport) {
            if (empty($importMappings[$requiredImport]['columns']['sku'])) {
                $errors[] = $requiredImport.' sku column is not mapped.';
            }
        }

        foreach ($importMappings as $importType => $mapping) {
            if (! empty($mapping['ambiguous'])) {
                $errors[] = $importType.' mapping is ambiguous and requires human review.';
            }

            if (empty($mapping['file_id'])) {
                $warnings[] = $importType.' mapping has no linked pilot file.';
            }
        }

        $manufacturerMapping = $pilot->manufacturer_form_mapping_json ?? [];

        if (empty(data_get($manufacturerMapping, 'items.start_row')) || empty(data_get($manufacturerMapping, 'items.columns.sku'))) {
            $errors[] = 'Manufacturer form item row mapping is incomplete.';
        }

        if (empty($pilot->email_sample_mapping_json['supplier_confirmation']['order_number'] ?? null)) {
            $warnings[] = 'Supplier confirmation email sample order number mapping is missing.';
        }

        if (empty($pilot->carrier_mapping_json['carrier_name'] ?? null) && empty($pilot->carrier_mapping_json['price'] ?? null)) {
            $warnings[] = 'Carrier quote sample mapping is incomplete.';
        }

        return [
            'status' => $errors === [] ? ($warnings === [] ? 'passed' : 'passed_with_warnings') : 'failed',
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function assertPilotFile(PilotSupplier $pilot, array $mapping): void
    {
        $fileId = $mapping['file_id'] ?? null;

        if ($fileId && ! $pilot->files()->whereKey((int) $fileId)->exists()) {
            throw ValidationException::withMessages([
                'mapping.file_id' => 'Mapped file does not belong to this pilot.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(PilotSupplier $pilot, User $user, string $mappingType, array $metadata = []): void
    {
        $this->auditLogService->write('pilot_mapping_saved', $pilot, $user, null, null, $metadata + [
            'pilot_supplier_id' => $pilot->id,
            'mapping_type' => $mappingType,
        ], $pilot->company_id);
    }
}
