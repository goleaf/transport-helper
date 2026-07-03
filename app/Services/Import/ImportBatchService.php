<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportAdapterInterface;
use App\Contracts\Import\ImportNormalizerInterface;
use App\Contracts\Import\ImportPersisterInterface;
use App\Contracts\Import\ImportValidatorInterface;
use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrderItem;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Import\Adapters\ApiImportAdapter;
use App\Services\Import\Adapters\CsvImportAdapter;
use App\Services\Import\Adapters\EmailAttachmentImportAdapter;
use App\Services\Import\Adapters\ExcelImportAdapter;
use App\Services\Import\Adapters\GoogleSheetsImportAdapter;
use App\Services\Import\Adapters\ManualJsonImportAdapter;
use App\Services\Import\Normalizers\InboundOrderNormalizer;
use App\Services\Import\Normalizers\ProductRuleNormalizer;
use App\Services\Import\Normalizers\ReservationNormalizer;
use App\Services\Import\Normalizers\SalesHistoryNormalizer;
use App\Services\Import\Normalizers\StockSnapshotNormalizer;
use App\Services\Import\Persisters\InboundOrderPersister;
use App\Services\Import\Persisters\ProductRulePersister;
use App\Services\Import\Persisters\ReservationPersister;
use App\Services\Import\Persisters\SalesHistoryPersister;
use App\Services\Import\Persisters\StockSnapshotPersister;
use App\Services\Import\Validators\InboundOrderValidator;
use App\Services\Import\Validators\ProductRuleValidator;
use App\Services\Import\Validators\ReservationValidator;
use App\Services\Import\Validators\SalesHistoryValidator;
use App\Services\Import\Validators\StockSnapshotValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportBatchService
{
    /**
     * @var list<string>
     */
    public const IMPORT_TYPES = [
        'sales_history',
        'stock_snapshot',
        'inbound_orders',
        'reservations',
        'product_rules',
        'supplier_products',
    ];

    public function __construct(
        private AuditLogService $auditLogService,
        private CsvImportAdapter $csvImportAdapter,
        private ManualJsonImportAdapter $manualJsonImportAdapter,
        private ExcelImportAdapter $excelImportAdapter,
        private GoogleSheetsImportAdapter $googleSheetsImportAdapter,
        private ApiImportAdapter $apiImportAdapter,
        private EmailAttachmentImportAdapter $emailAttachmentImportAdapter,
        private SalesHistoryNormalizer $salesHistoryNormalizer,
        private StockSnapshotNormalizer $stockSnapshotNormalizer,
        private InboundOrderNormalizer $inboundOrderNormalizer,
        private ReservationNormalizer $reservationNormalizer,
        private ProductRuleNormalizer $productRuleNormalizer,
        private SalesHistoryValidator $salesHistoryValidator,
        private StockSnapshotValidator $stockSnapshotValidator,
        private InboundOrderValidator $inboundOrderValidator,
        private ReservationValidator $reservationValidator,
        private ProductRuleValidator $productRuleValidator,
        private SalesHistoryPersister $salesHistoryPersister,
        private StockSnapshotPersister $stockSnapshotPersister,
        private InboundOrderPersister $inboundOrderPersister,
        private ReservationPersister $reservationPersister,
        private ProductRulePersister $productRulePersister,
    ) {}

    /**
     * Backward-compatible wrapper for the previous import API.
     *
     * @param  array<string, mixed>  $input
     */
    public function import(array $input): ImportBatch
    {
        $user = isset($input['started_by_user_id'])
            ? User::query()->find($input['started_by_user_id'])
            : null;

        $result = $this->run(
            (string) $input['import_type'],
            (string) ($input['adapter'] ?? 'csv'),
            [
                'file_path' => $input['source_path'] ?? $input['file_path'] ?? null,
                'delimiter' => $input['delimiter'] ?? ($input['adapter_options']['delimiter'] ?? ','),
                'has_header' => $input['has_header'] ?? ($input['adapter_options']['has_header'] ?? true),
                'rows' => $input['rows'] ?? null,
            ],
            [
                'company_id' => $input['company_id'],
                'supplier_id' => $input['supplier_id'] ?? null,
                'dry_run' => $input['dry_run'] ?? false,
                'source_type' => $input['source_type'] ?? ($input['adapter'] ?? 'csv'),
                'source_name' => $input['source_name'] ?? ($input['source_reference'] ?? null),
                'original_filename' => $input['original_filename'] ?? null,
                'allow_duplicate' => $input['allow_duplicate'] ?? false,
                'allow_negative_stock' => $input['allow_negative_stock'] ?? false,
            ],
            $user instanceof User ? $user : null,
        );

        return $result['batch'];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array{batch:ImportBatch,summary:array<string,mixed>}
     */
    public function run(string $importType, string $adapterName, array $config, array $options = [], ?User $user = null): array
    {
        $companyId = (int) ($options['company_id'] ?? 0);

        if (! Company::query()->whereKey($companyId)->exists()) {
            throw new RuntimeException('Import requires a valid company_id.');
        }

        $adapter = $this->resolveAdapter($adapterName);
        $normalizer = $this->resolveNormalizer($importType);
        $validator = $this->resolveValidator($importType);
        $persister = (bool) ($options['dry_run'] ?? false) ? null : $this->resolvePersister($importType);
        $checksum = $this->checksum($config, $options);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $warnings = [];

        if ($checksum !== null && ! (bool) ($options['allow_duplicate'] ?? false) && $this->hasCompletedDuplicate($companyId, $importType, $checksum)) {
            $batch = $this->createBatch($companyId, $importType, $adapterName, $checksum, $options, ImportBatchStatus::Failed, $user);
            $batch->update([
                'finished_at' => now(),
                'error_summary' => 'duplicate_import_checksum',
            ]);

            $this->auditLogService->logImport($batch->refresh(), 'import_duplicate_blocked', $user, $this->auditMetadata($importType, $adapterName, $dryRun, $checksum, $options));

            return [
                'batch' => $batch->refresh()->load('rows'),
                'summary' => [
                    'total_rows' => 0,
                    'successful_rows' => 0,
                    'failed_rows' => 0,
                    'dry_run' => $dryRun,
                    'warnings' => ['duplicate_import_checksum'],
                ],
            ];
        }

        $batch = $this->createBatch(
            $companyId,
            $importType,
            $adapterName,
            $checksum,
            $options,
            $dryRun ? ImportBatchStatus::DryRun : ImportBatchStatus::Processing,
            $user,
        );

        $this->auditLogService->logImport($batch, 'import_started', $user, $this->auditMetadata($importType, $adapterName, $dryRun, $checksum, $options));

        try {
            $rawRows = $adapter->read($config);
        } catch (Throwable $exception) {
            $batch->update([
                'status' => ImportBatchStatus::Failed->value,
                'finished_at' => now(),
                'error_summary' => $exception->getMessage(),
            ]);
            $this->auditLogService->logImport($batch->refresh(), 'import_failed', $user, $this->auditMetadata($importType, $adapterName, $dryRun, $checksum, $options));

            throw $exception;
        }

        $successfulRows = 0;
        $failedRows = 0;

        foreach (array_values($rawRows) as $index => $rawRow) {
            $rowNumber = $index + 1;
            $importRow = ImportRow::query()->create([
                'import_batch_id' => $batch->getKey(),
                'row_number' => $rowNumber,
                'raw_json' => $rawRow,
                'normalized_json' => null,
                'status' => ImportRowStatus::Pending->value,
                'error_message' => null,
            ]);

            $rowContext = $this->context($batch, $options, $rowNumber);
            $normalized = $normalizer->normalize($rawRow, $rowContext);
            $validation = $validator->validate($normalized, $rowContext);
            $normalized = $validation['normalized'];

            if ($validation['warnings'] !== []) {
                $warnings = array_values(array_unique(array_merge($warnings, $validation['warnings'])));
            }

            if (! $validation['valid']) {
                $failedRows++;
                $importRow->update([
                    'normalized_json' => $normalized,
                    'status' => ImportRowStatus::Invalid->value,
                    'error_message' => implode(' ', $validation['errors']),
                ]);

                continue;
            }

            if ($dryRun) {
                $successfulRows++;
                $importRow->update([
                    'normalized_json' => $normalized,
                    'status' => ImportRowStatus::Valid->value,
                ]);

                continue;
            }

            try {
                $persisted = DB::transaction(fn (): array => $this->persistRow($persister, $normalized, $rowContext));
                $successfulRows++;
                $importRow->update([
                    'normalized_json' => $normalized,
                    'status' => ImportRowStatus::Persisted->value,
                    'related_model_type' => $persisted['model_type'],
                    'related_model_id' => $persisted['model_id'],
                ]);
            } catch (Throwable $exception) {
                $failedRows++;
                $importRow->update([
                    'normalized_json' => $normalized,
                    'status' => ImportRowStatus::Failed->value,
                    'error_message' => $exception->getMessage(),
                ]);
            }
        }

        $status = $this->finalStatus($dryRun, $successfulRows, $failedRows);
        $batch->update([
            'total_rows' => count($rawRows),
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'status' => $status->value,
            'finished_at' => now(),
            'error_summary' => $this->errorSummary($failedRows, $warnings),
        ]);

        $eventType = match ($status) {
            ImportBatchStatus::Completed => 'import_completed',
            ImportBatchStatus::CompletedWithErrors => 'import_completed_with_errors',
            ImportBatchStatus::Failed => 'import_failed',
            ImportBatchStatus::DryRun => 'import_completed',
            default => 'import_completed',
        };
        $batch = $batch->refresh();
        $this->auditLogService->logImport($batch, $eventType, $user, $this->auditMetadata($importType, $adapterName, $dryRun, $checksum, $options) + [
            'total_rows' => $batch->total_rows,
            'successful_rows' => $batch->successful_rows,
            'failed_rows' => $batch->failed_rows,
            'warnings' => $warnings,
        ]);

        return [
            'batch' => $batch->load('rows'),
            'summary' => [
                'total_rows' => $batch->total_rows,
                'successful_rows' => $batch->successful_rows,
                'failed_rows' => $batch->failed_rows,
                'dry_run' => $dryRun,
                'warnings' => $warnings,
            ],
        ];
    }

    /**
     * @return array{rolled_back_count:int,skipped_count:int,skipped_reasons:list<string>}
     */
    public function rollback(ImportBatch $batch, ?User $user = null): array
    {
        if ($this->statusValue($batch->status) === ImportBatchStatus::DryRun->value) {
            return [
                'rolled_back_count' => 0,
                'skipped_count' => 0,
                'skipped_reasons' => ['dry_run_has_no_domain_records'],
            ];
        }

        if ($this->statusValue($batch->status) === ImportBatchStatus::RolledBack->value) {
            return [
                'rolled_back_count' => 0,
                'skipped_count' => 0,
                'skipped_reasons' => ['batch_already_rolled_back'],
            ];
        }

        $rolledBack = 0;
        $skippedReasons = [];

        $batch->rows()
            ->select(['id', 'related_model_type', 'related_model_id', 'status'])
            ->where('status', ImportRowStatus::Persisted->value)
            ->whereNotNull('related_model_type')
            ->whereNotNull('related_model_id')
            ->get()
            ->each(function (ImportRow $row) use (&$rolledBack, &$skippedReasons): void {
                $result = $this->rollbackRow($row);

                if ($result === true) {
                    $rolledBack++;

                    return;
                }

                $skippedReasons[] = $result;
            });

        $batch->update([
            'status' => ImportBatchStatus::RolledBack->value,
            'error_summary' => trim((string) $batch->error_summary.' rolled_back'),
        ]);
        $batch = $batch->refresh();

        $this->auditLogService->logImport($batch, 'import_rolled_back', $user, [
            'rolled_back_count' => $rolledBack,
            'skipped_count' => count($skippedReasons),
            'skipped_reasons' => array_values(array_unique($skippedReasons)),
        ]);

        return [
            'rolled_back_count' => $rolledBack,
            'skipped_count' => count($skippedReasons),
            'skipped_reasons' => array_values(array_unique($skippedReasons)),
        ];
    }

    protected function resolveAdapter(string $adapterName): ImportAdapterInterface
    {
        return match ($adapterName) {
            'csv' => $this->csvImportAdapter,
            'manual_json' => $this->manualJsonImportAdapter,
            'excel' => $this->excelImportAdapter,
            'google_sheets' => $this->googleSheetsImportAdapter,
            'api' => $this->apiImportAdapter,
            'email_attachment' => $this->emailAttachmentImportAdapter,
            default => throw new RuntimeException("Unknown import adapter [{$adapterName}]."),
        };
    }

    protected function resolveNormalizer(string $importType): ImportNormalizerInterface
    {
        return match ($importType) {
            'sales_history' => $this->salesHistoryNormalizer,
            'stock_snapshot' => $this->stockSnapshotNormalizer,
            'inbound_orders' => $this->inboundOrderNormalizer,
            'reservations' => $this->reservationNormalizer,
            'product_rules', 'supplier_products' => $this->productRuleNormalizer,
            default => throw new RuntimeException("Import type [{$importType}] is not configured yet."),
        };
    }

    protected function resolveValidator(string $importType): ImportValidatorInterface
    {
        return match ($importType) {
            'sales_history' => $this->salesHistoryValidator,
            'stock_snapshot' => $this->stockSnapshotValidator,
            'inbound_orders' => $this->inboundOrderValidator,
            'reservations' => $this->reservationValidator,
            'product_rules', 'supplier_products' => $this->productRuleValidator,
            default => throw new RuntimeException("Import type [{$importType}] is not configured yet."),
        };
    }

    protected function resolvePersister(string $importType): ImportPersisterInterface
    {
        return match ($importType) {
            'sales_history' => $this->salesHistoryPersister,
            'stock_snapshot' => $this->stockSnapshotPersister,
            'inbound_orders' => $this->inboundOrderPersister,
            'reservations' => $this->reservationPersister,
            'product_rules', 'supplier_products' => $this->productRulePersister,
            default => throw new RuntimeException("Import type [{$importType}] is not configured yet."),
        };
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function createBatch(int $companyId, string $importType, string $adapterName, ?string $checksum, array $options, ImportBatchStatus $status, ?User $user): ImportBatch
    {
        return ImportBatch::query()->create([
            'company_id' => $companyId,
            'import_type' => $importType,
            'source_type' => $options['source_type'] ?? $adapterName,
            'source_name' => $options['source_name'] ?? null,
            'adapter' => $adapterName,
            'original_filename' => $options['original_filename'] ?? null,
            'checksum' => $checksum,
            'status' => $status->value,
            'total_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'started_by_user_id' => $user?->getKey(),
            'started_at' => now(),
            'finished_at' => null,
            'error_summary' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function context(ImportBatch $batch, array $options, int $rowNumber): array
    {
        return [
            'company_id' => (int) $batch->company_id,
            'supplier_id' => $options['supplier_id'] ?? null,
            'source_type' => $options['source_type'] ?? $batch->adapter,
            'source_reference' => $this->rowSourceReference($batch, $rowNumber),
            'import_batch_id' => $batch->getKey(),
            'allow_negative_stock' => (bool) ($options['allow_negative_stock'] ?? false),
            'allow_product_update' => (bool) ($options['allow_product_update'] ?? false),
        ];
    }

    private function rowSourceReference(ImportBatch $batch, int $rowNumber): string
    {
        return 'import_batch_'.$batch->getKey().'_row_'.$rowNumber;
    }

    /**
     * @return array{model_type:class-string,model_id:int,model:object,metadata?:array<string,mixed>}
     */
    private function persistRow(?ImportPersisterInterface $persister, array $normalized, array $context): array
    {
        if (! $persister instanceof ImportPersisterInterface) {
            throw new RuntimeException('Import persister is not configured.');
        }

        return $persister->persist($normalized, $context);
    }

    private function finalStatus(bool $dryRun, int $successfulRows, int $failedRows): ImportBatchStatus
    {
        if ($dryRun) {
            return ImportBatchStatus::DryRun;
        }

        if ($failedRows === 0) {
            return ImportBatchStatus::Completed;
        }

        return $successfulRows > 0
            ? ImportBatchStatus::CompletedWithErrors
            : ImportBatchStatus::Failed;
    }

    /**
     * @param  list<string>  $warnings
     */
    private function errorSummary(int $failedRows, array $warnings): ?string
    {
        $messages = [];

        if ($failedRows > 0) {
            $messages[] = $failedRows.' row(s) failed validation or persistence';
        }

        foreach ($warnings as $warning) {
            $messages[] = $warning;
        }

        return $messages === [] ? null : implode('; ', array_values(array_unique($messages)));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    private function checksum(array $config, array $options): ?string
    {
        if (isset($options['checksum']) && is_string($options['checksum'])) {
            return $options['checksum'];
        }

        $filePath = $config['file_path'] ?? null;

        if (is_string($filePath) && is_readable($filePath)) {
            $checksum = hash_file('sha256', $filePath);

            return $checksum === false ? null : $checksum;
        }

        if (isset($config['rows']) && is_array($config['rows'])) {
            return hash('sha256', json_encode($config['rows'], JSON_THROW_ON_ERROR));
        }

        return null;
    }

    private function hasCompletedDuplicate(int $companyId, string $importType, string $checksum): bool
    {
        return ImportBatch::query()
            ->where('company_id', $companyId)
            ->where('import_type', $importType)
            ->where('checksum', $checksum)
            ->whereIn('status', [
                ImportBatchStatus::Completed->value,
                ImportBatchStatus::CompletedWithErrors->value,
            ])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function auditMetadata(string $importType, string $adapterName, bool $dryRun, ?string $checksum, array $options): array
    {
        return [
            'import_type' => $importType,
            'adapter' => $adapterName,
            'dry_run' => $dryRun,
            'checksum' => $checksum,
            'original_filename' => $options['original_filename'] ?? null,
        ];
    }

    private function rollbackRow(ImportRow $row): true|string
    {
        $modelClass = $row->related_model_type;
        $modelId = $row->related_model_id;

        if (! is_string($modelClass) || ! is_numeric($modelId) || ! is_a($modelClass, Model::class, true)) {
            return 'invalid_related_model';
        }

        if ($modelClass === SupplierProductRule::class) {
            return 'unsafe_product_rule_rollback';
        }

        if (! in_array($modelClass, [SalesHistory::class, StockSnapshot::class, Reservation::class, InboundOrderItem::class], true)) {
            return 'unsupported_rollback_model_'.$modelClass;
        }

        $model = $modelClass::query()->find((int) $modelId);

        if (! $model instanceof Model) {
            return 'related_model_missing';
        }

        $model->delete();

        return true;
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? $status->value : (is_string($status) ? $status : null);
    }
}
