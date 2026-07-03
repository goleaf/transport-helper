<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotFileType;
use App\Models\CarrierContact;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\Product;
use App\Models\SupplierProductRule;
use App\Services\Audit\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PilotDataQualityService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(PilotSupplier $pilot): array
    {
        $pilot->loadMissing(['files:id,pilot_supplier_id,file_type,stored_path,original_filename', 'supplier:id,company_id']);

        $checks = [];
        $warnings = [];
        $errors = [];
        $filesByType = $pilot->files->groupBy('file_type');

        foreach (PilotFileType::requiredValues() as $fileType) {
            $present = $filesByType->has($fileType);
            $this->pushCheck($checks, $present ? 'ok' : 'error', $fileType.'_present', $present ? 'Required pilot file is present.' : 'Required pilot file is missing.');

            if (! $present) {
                $errors[] = $fileType.' is missing.';
            }
        }

        $hasProductRules = SupplierProductRule::query()
            ->where('supplier_id', $pilot->supplier_id)
            ->whereNotNull('pack_multiple')
            ->whereNotNull('safety_days')
            ->exists();
        $hasProductRuleFile = $filesByType->has(PilotFileType::ProductRulesSample->value);

        $this->pushCheck($checks, $hasProductRules || $hasProductRuleFile ? 'ok' : 'warning', 'supplier_product_rules', $hasProductRules || $hasProductRuleFile ? 'Supplier product rules are available.' : 'Supplier product rules sample or configured rules are missing.');

        if (! $hasProductRules && ! $hasProductRuleFile) {
            $warnings[] = 'Supplier product rules sample or configured rules are missing.';
        }

        $supplierContactExists = $pilot->supplier
            ->contacts()
            ->where('receives_orders', true)
            ->where('is_active', true)
            ->exists();
        $this->pushCheck($checks, $supplierContactExists ? 'ok' : 'warning', 'supplier_contact_receives_orders', $supplierContactExists ? 'Supplier order contact exists.' : 'Supplier order contact is missing.');

        if (! $supplierContactExists) {
            $warnings[] = 'Supplier contact with receives_orders is missing.';
        }

        $carrierContactExists = CarrierContact::query()
            ->where('is_active', true)
            ->whereHas('carrier', fn ($query) => $query->where('company_id', $pilot->company_id))
            ->exists();
        $this->pushCheck($checks, $carrierContactExists ? 'ok' : 'warning', 'carrier_contacts', $carrierContactExists ? 'Carrier contacts exist.' : 'Carrier contacts are missing.');

        if (! $carrierContactExists) {
            $warnings[] = 'Carrier contact is missing.';
        }

        foreach ($pilot->files as $file) {
            $fileResult = $this->inspectFile($pilot, $file);
            array_push($checks, ...$fileResult['checks']);
            array_push($warnings, ...$fileResult['warnings']);
            array_push($errors, ...$fileResult['errors']);
        }

        $mappingResult = app(PilotMappingService::class)->validateMappings($pilot);
        foreach ($mappingResult['warnings'] as $warning) {
            $this->pushCheck($checks, 'warning', 'mapping_warning', $warning);
            $warnings[] = $warning;
        }
        foreach ($mappingResult['errors'] as $error) {
            $this->pushCheck($checks, 'error', 'mapping_error', $error);
            $errors[] = $error;
        }

        $status = $errors !== []
            ? 'failed'
            : ($warnings !== [] ? 'passed_with_warnings' : 'passed');

        $result = [
            'status' => $status,
            'checks' => $checks,
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
        ];

        $this->auditLogService->write('pilot_data_quality_checked', $pilot, null, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'status' => $status,
            'warning_count' => count($result['warnings']),
            'error_count' => count($result['errors']),
        ], $pilot->company_id);

        return $result;
    }

    /**
     * @return array{checks:list<array<string,string>>,warnings:list<string>,errors:list<string>}
     */
    private function inspectFile(PilotSupplier $pilot, PilotFile $file): array
    {
        $checks = [];
        $warnings = [];
        $errors = [];

        if (! Storage::disk('local')->exists($file->stored_path)) {
            $this->pushCheck($checks, 'error', 'file_readable_'.$file->id, $file->original_filename.' is not readable in private storage.');
            $errors[] = $file->original_filename.' is not readable.';

            return compact('checks', 'warnings', 'errors');
        }

        $this->pushCheck($checks, 'ok', 'file_readable_'.$file->id, $file->original_filename.' is readable in private storage.');

        if (in_array(pathinfo($file->stored_path, PATHINFO_EXTENSION), ['csv', 'txt'], true)) {
            $csvResult = $this->inspectCsvFile($pilot, $file);
            array_push($checks, ...$csvResult['checks']);
            array_push($warnings, ...$csvResult['warnings']);
            array_push($errors, ...$csvResult['errors']);
        }

        if (in_array($file->file_type, [PilotFileType::SupplierConfirmationEmailSample->value, PilotFileType::CarrierQuoteEmailSample->value], true)) {
            $content = Storage::disk('local')->get($file->stored_path);
            $hasReference = preg_match('/\b(order|po|reference|supplier|quote)\b/i', $content) === 1;
            $this->pushCheck($checks, $hasReference ? 'ok' : 'warning', 'email_reference_'.$file->id, $hasReference ? 'Email sample contains an order or supplier reference.' : 'Email sample may not contain an order or supplier reference.');

            if (! $hasReference) {
                $warnings[] = $file->original_filename.' has no obvious order or supplier reference.';
            }
        }

        return compact('checks', 'warnings', 'errors');
    }

    /**
     * @return array{checks:list<array<string,string>>,warnings:list<string>,errors:list<string>}
     */
    private function inspectCsvFile(PilotSupplier $pilot, PilotFile $file): array
    {
        $checks = [];
        $warnings = [];
        $errors = [];
        $content = Storage::disk('local')->get($file->stored_path);
        $rows = collect(preg_split('/\R/', trim($content)) ?: [])
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->take(21)
            ->values();

        if ($rows->isEmpty()) {
            $errors[] = $file->original_filename.' is empty.';
            $this->pushCheck($checks, 'error', 'file_not_empty_'.$file->id, 'Sample CSV is empty.');

            return compact('checks', 'warnings', 'errors');
        }

        $header = str_getcsv((string) $rows->first());
        $records = $rows->slice(1)->map(fn (string $line): array => array_combine($header, array_pad(str_getcsv($line), count($header), null)) ?: []);
        $mapping = $this->mappingForFile($pilot, $file);
        $columns = $mapping['columns'] ?? [];

        foreach ($columns as $field => $column) {
            $exists = in_array($column, $header, true);
            $this->pushCheck($checks, $exists ? 'ok' : 'error', 'mapped_column_'.$file->id.'_'.$field, $exists ? $field.' column exists.' : $field.' column '.$column.' is missing.');

            if (! $exists) {
                $errors[] = $file->original_filename.' mapped column '.$column.' is missing.';
            }
        }

        $skuColumn = $columns['sku'] ?? null;
        $dateColumn = $columns['sales_date'] ?? $columns['snapshot_date'] ?? $columns['date'] ?? null;
        $quantityColumn = $columns['quantity'] ?? $columns['free_stock'] ?? $columns['ordered_quantity'] ?? null;

        if ($skuColumn && in_array($skuColumn, $header, true)) {
            $skus = $records->map(fn (array $row): string => trim((string) ($row[$skuColumn] ?? '')))->filter();
            $this->pushCheck($checks, $skus->isNotEmpty() ? 'ok' : 'error', 'sku_values_'.$file->id, $skus->isNotEmpty() ? 'SKU values are present.' : 'SKU values are missing.');

            if ($skus->isEmpty()) {
                $errors[] = $file->original_filename.' has empty SKU values.';
            }

            $duplicates = $skus->duplicates()->unique()->values();
            if ($duplicates->isNotEmpty()) {
                $warnings[] = $file->original_filename.' has duplicate SKUs: '.$duplicates->take(5)->implode(', ');
            }

            $knownSkus = Product::query()
                ->where('company_id', $pilot->company_id)
                ->whereIn('sku', $skus->unique()->values()->all())
                ->limit(5000)
                ->pluck('sku')
                ->all();
            $unknown = $skus->unique()->diff($knownSkus)->values();

            if ($unknown->isNotEmpty()) {
                $warnings[] = $file->original_filename.' has unknown SKUs: '.$unknown->take(5)->implode(', ');
            }
        }

        if ($dateColumn && in_array($dateColumn, $header, true)) {
            $invalidDates = $this->invalidDateCount($records, $dateColumn);
            $this->pushCheck($checks, $invalidDates === 0 ? 'ok' : 'error', 'date_parse_'.$file->id, $invalidDates === 0 ? 'Date values parse.' : $invalidDates.' date values do not parse.');

            if ($invalidDates > 0) {
                $errors[] = $file->original_filename.' has invalid dates.';
            }
        }

        if ($quantityColumn && in_array($quantityColumn, $header, true)) {
            $invalidQuantities = $records->filter(fn (array $row): bool => ! is_numeric($row[$quantityColumn] ?? null))->count();
            $this->pushCheck($checks, $invalidQuantities === 0 ? 'ok' : 'error', 'quantity_parse_'.$file->id, $invalidQuantities === 0 ? 'Quantity values parse.' : $invalidQuantities.' quantity values do not parse.');

            if ($invalidQuantities > 0) {
                $errors[] = $file->original_filename.' has invalid quantities.';
            }
        }

        return compact('checks', 'warnings', 'errors');
    }

    /**
     * @return array<string, mixed>
     */
    private function mappingForFile(PilotSupplier $pilot, PilotFile $file): array
    {
        return collect($pilot->import_mappings_json ?? [])
            ->first(fn (array $mapping): bool => (int) ($mapping['file_id'] ?? 0) === $file->id) ?? [];
    }

    private function invalidDateCount(Collection $records, string $dateColumn): int
    {
        return $records->filter(function (array $row) use ($dateColumn): bool {
            try {
                CarbonImmutable::parse((string) ($row[$dateColumn] ?? ''));

                return false;
            } catch (\Throwable) {
                return true;
            }
        })->count();
    }

    /**
     * @param  list<array<string,string>>  $checks
     */
    private function pushCheck(array &$checks, string $status, string $key, string $message): void
    {
        $checks[] = [
            'key' => $key,
            'status' => $status,
            'message' => $message,
        ];
    }
}
