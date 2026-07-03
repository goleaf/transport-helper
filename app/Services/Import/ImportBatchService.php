<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportAdapterInterface;
use App\Contracts\Import\ImportNormalizerInterface;
use App\Contracts\Import\ImportValidatorInterface;
use App\Enums\ImportBatchStatus;
use App\Imports\Adapters\ApiImportAdapter;
use App\Imports\Adapters\CsvImportAdapter;
use App\Imports\Adapters\EmailAttachmentImportAdapter;
use App\Imports\Adapters\ExcelImportAdapter;
use App\Imports\Adapters\GoogleSheetsImportAdapter;
use App\Imports\Adapters\ManualJsonImportAdapter;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
        'carrier_quotes',
        'logistics_records',
    ];

    public function __construct(
        private CsvImportAdapter $csvImportAdapter,
        private ExcelImportAdapter $excelImportAdapter,
        private GoogleSheetsImportAdapter $googleSheetsImportAdapter,
        private ApiImportAdapter $apiImportAdapter,
        private ManualJsonImportAdapter $manualJsonImportAdapter,
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
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function import(array $input): ImportBatch
    {
        $companyId = (int) $input['company_id'];
        $importType = (string) $input['import_type'];
        $adapterName = (string) ($input['adapter'] ?? 'csv');
        $sourcePath = (string) $input['source_path'];
        $originalFilename = $input['original_filename'] ?? null;
        $sourceName = $input['source_reference'] ?? $originalFilename;
        $dryRun = (bool) ($input['dry_run'] ?? false);
        $startedByUserId = $input['started_by_user_id'] ?? null;
        $adapter = $this->adapter($adapterName);
        $checksum = $adapter->checksum($sourcePath);
        $parsedRows = $adapter->rows($sourcePath, $input['adapter_options'] ?? []);
        $duplicate = $this->isDuplicateFile($companyId, $importType, $checksum, $sourceName);
        $context = $this->context($companyId, $parsedRows);

        return DB::transaction(function () use (
            $companyId,
            $importType,
            $adapterName,
            $originalFilename,
            $sourceName,
            $dryRun,
            $startedByUserId,
            $checksum,
            $parsedRows,
            $duplicate,
            $context,
        ): ImportBatch {
            $now = now();
            $batch = ImportBatch::query()->create([
                'company_id' => $companyId,
                'source_type' => $importType,
                'source_name' => $sourceName,
                'adapter' => $adapterName,
                'original_filename' => $originalFilename,
                'checksum' => $checksum,
                'status' => $dryRun ? ImportBatchStatus::DryRun->value : ImportBatchStatus::Processing->value,
                'total_rows' => count($parsedRows),
                'successful_rows' => 0,
                'failed_rows' => 0,
                'started_by_user_id' => $startedByUserId,
                'started_at' => $now,
                'finished_at' => null,
                'error_summary' => $duplicate ? 'duplicate_file_warning' : null,
            ]);

            $prepared = $this->prepareRows($batch, $importType, $parsedRows, $context);
            $relatedModels = $dryRun ? [] : $this->persistDomainRows($batch, $importType, $prepared['valid_rows'], $context);
            $importRows = $this->importRowPayload($batch, $prepared, $relatedModels, $dryRun, $now);

            if ($importRows !== []) {
                ImportRow::query()->insert($importRows);
            }

            $summary = $this->summary($duplicate, $prepared['failed_rows']);
            $status = $this->finalStatus($dryRun, count($prepared['failed_rows']));

            $batch->update([
                'status' => $status,
                'successful_rows' => count($prepared['valid_rows']),
                'failed_rows' => count($prepared['failed_rows']),
                'finished_at' => $now,
                'error_summary' => $summary,
            ]);

            $this->audit('import_batch.created', $batch, [
                'import_type' => $importType,
                'adapter' => $adapterName,
                'dry_run' => $dryRun,
                'duplicate_file' => $duplicate,
                'total_rows' => count($parsedRows),
                'successful_rows' => count($prepared['valid_rows']),
                'failed_rows' => count($prepared['failed_rows']),
            ], $startedByUserId);

            return $batch->refresh()->load(['rows', 'company']);
        });
    }

    public function rollback(ImportBatch $batch, ?int $userId = null): ImportBatch
    {
        return DB::transaction(function () use ($batch, $userId): ImportBatch {
            $rows = $batch->rows()
                ->select(['id', 'related_model_type', 'related_model_id'])
                ->where('status', 'successful')
                ->whereNotNull('related_model_type')
                ->whereNotNull('related_model_id')
                ->get();

            $deleted = 0;

            foreach ($rows->groupBy('related_model_type') as $modelClass => $groupedRows) {
                if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true)) {
                    continue;
                }

                $deleted += $modelClass::query()
                    ->whereKey($groupedRows->pluck('related_model_id')->all())
                    ->delete();
            }

            $batch->update([
                'status' => ImportBatchStatus::RolledBack->value,
                'error_summary' => trim((string) $batch->error_summary.' rolled_back'),
            ]);

            $this->audit('import_batch.rolled_back', $batch, [
                'deleted_related_models' => $deleted,
            ], $userId);

            return $batch->refresh()->load(['rows', 'company']);
        });
    }

    private function adapter(string $adapter): ImportAdapterInterface
    {
        return match ($adapter) {
            'csv' => $this->csvImportAdapter,
            'excel' => $this->excelImportAdapter,
            'google_sheets' => $this->googleSheetsImportAdapter,
            'api' => $this->apiImportAdapter,
            'manual_json' => $this->manualJsonImportAdapter,
            'email_attachment' => $this->emailAttachmentImportAdapter,
            default => throw new RuntimeException("Unknown import adapter [{$adapter}]."),
        };
    }

    private function normalizer(string $importType): ImportNormalizerInterface
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

    private function validator(string $importType): ImportValidatorInterface
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

    /**
     * @param  list<array{row_number:int,data:array<string,mixed>}>  $parsedRows
     * @return array<string, mixed>
     */
    private function context(int $companyId, array $parsedRows): array
    {
        $skus = [];
        $supplierCodes = [];

        foreach ($parsedRows as $parsedRow) {
            $sku = trim((string) ($parsedRow['data']['sku'] ?? ''));
            $supplierCode = trim((string) ($parsedRow['data']['supplier_code'] ?? ''));

            if ($sku !== '') {
                $skus[] = $sku;
            }

            if ($supplierCode !== '') {
                $supplierCodes[] = $supplierCode;
            }
        }

        $products = $skus === []
            ? collect()
            : Product::query()
                ->select(['id', 'company_id', 'sku'])
                ->where('company_id', $companyId)
                ->whereIn('sku', array_values(array_unique($skus)))
                ->get();
        $suppliers = $supplierCodes === []
            ? collect()
            : Supplier::query()
                ->select(['id', 'company_id', 'code'])
                ->where('company_id', $companyId)
                ->whereIn('code', array_values(array_unique($supplierCodes)))
                ->get();

        return [
            'company_id' => $companyId,
            'products_by_sku' => $products
                ->mapWithKeys(fn (Product $product): array => [strtoupper($product->sku) => $product])
                ->all(),
            'suppliers_by_code' => $suppliers
                ->mapWithKeys(fn (Supplier $supplier): array => [strtoupper((string) $supplier->code) => $supplier])
                ->all(),
        ];
    }

    /**
     * @param  list<array{row_number:int,data:array<string,mixed>}>  $parsedRows
     * @param  array<string, mixed>  $context
     * @return array{valid_rows:list<array<string,mixed>>,failed_rows:list<array<string,mixed>>}
     */
    private function prepareRows(ImportBatch $batch, string $importType, array $parsedRows, array $context): array
    {
        $normalizer = $this->normalizer($importType);
        $validator = $this->validator($importType);
        $validRows = [];
        $failedRows = [];

        foreach ($parsedRows as $parsedRow) {
            $normalized = $normalizer->normalize($parsedRow['data'], $context);
            $normalized['source_reference'] = $this->rowSourceReference($batch, $parsedRow['row_number']);
            $errors = $validator->validate($normalized, $context);

            if ($errors !== []) {
                $failedRows[] = [
                    'row_number' => $parsedRow['row_number'],
                    'raw' => $parsedRow['data'],
                    'normalized' => $normalized,
                    'errors' => $errors,
                ];

                continue;
            }

            $validRows[] = [
                'row_number' => $parsedRow['row_number'],
                'raw' => $parsedRow['data'],
                'normalized' => $this->withResolvedIds($normalized, $context),
            ];
        }

        return [
            'valid_rows' => $validRows,
            'failed_rows' => $failedRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withResolvedIds(array $row, array $context): array
    {
        if (isset($row['sku'])) {
            $product = $context['products_by_sku'][strtoupper((string) $row['sku'])] ?? null;
            $row['product_id'] = $product?->getKey();
        }

        if (isset($row['supplier_code'])) {
            $supplier = $context['suppliers_by_code'][strtoupper((string) $row['supplier_code'])] ?? null;
            $row['supplier_id'] = $supplier?->getKey();
        }

        return $row;
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @param  array<string, mixed>  $context
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistDomainRows(ImportBatch $batch, string $importType, array $validRows, array $context): array
    {
        if ($validRows === []) {
            return [];
        }

        return match ($importType) {
            'sales_history' => $this->persistSalesHistory($batch, $validRows),
            'stock_snapshot' => $this->persistStockSnapshots($batch, $validRows),
            'reservations' => $this->persistReservations($batch, $validRows),
            'product_rules', 'supplier_products' => $this->persistProductRules($validRows),
            'inbound_orders' => $this->persistInboundOrders($batch, $validRows, $context),
            default => throw new RuntimeException("Import type [{$importType}] is not configured yet."),
        };
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistSalesHistory(ImportBatch $batch, array $validRows): array
    {
        $now = now();
        $payload = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $payload[] = [
                'company_id' => $batch->company_id,
                'product_id' => $row['product_id'],
                'sales_date' => $row['sales_date'],
                'quantity' => $row['quantity'],
                'channel' => $row['channel'],
                'customer_id' => $row['customer_id'],
                'is_promotion' => $row['is_promotion'],
                'is_anomaly' => $row['is_anomaly'],
                'anomaly_reason' => $row['anomaly_reason'],
                'source_type' => $row['source_type'],
                'source_reference' => $row['source_reference'],
                'import_batch_id' => $batch->getKey(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SalesHistory::query()->insert($payload);

        return $this->relatedMap(
            SalesHistory::query()
                ->select(['id', 'source_reference'])
                ->where('import_batch_id', $batch->getKey())
                ->get(),
            SalesHistory::class,
            $validRows,
        );
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistStockSnapshots(ImportBatch $batch, array $validRows): array
    {
        $now = now();
        $payload = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $payload[] = [
                'company_id' => $batch->company_id,
                'product_id' => $row['product_id'],
                'snapshot_date' => $row['snapshot_date'],
                'free_stock' => $row['free_stock'],
                'total_stock' => $row['total_stock'],
                'reserved_quantity' => $row['reserved_quantity'],
                'damaged_quantity' => $row['damaged_quantity'],
                'inactive_quantity' => $row['inactive_quantity'],
                'in_transit_quantity' => $row['in_transit_quantity'],
                'source_type' => $row['source_type'],
                'source_reference' => $row['source_reference'],
                'import_batch_id' => $batch->getKey(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        StockSnapshot::query()->insert($payload);

        return $this->relatedMap(
            StockSnapshot::query()
                ->select(['id', 'source_reference'])
                ->where('import_batch_id', $batch->getKey())
                ->get(),
            StockSnapshot::class,
            $validRows,
        );
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistReservations(ImportBatch $batch, array $validRows): array
    {
        $now = now();
        $payload = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $payload[] = [
                'company_id' => $batch->company_id,
                'product_id' => $row['product_id'],
                'quantity' => $row['quantity'],
                'project_name' => $row['project_name'],
                'customer_name' => $row['customer_name'],
                'manager_name' => $row['manager_name'],
                'reserved_at' => $row['reserved_at'],
                'expected_usage_date' => $row['expected_usage_date'],
                'status' => $row['status'],
                'source_type' => $row['source_type'],
                'source_reference' => $row['source_reference'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Reservation::query()->insert($payload);

        return $this->relatedMap(
            Reservation::query()
                ->select(['id', 'source_reference'])
                ->where('company_id', $batch->company_id)
                ->whereIn('source_reference', array_column($payload, 'source_reference'))
                ->get(),
            Reservation::class,
            $validRows,
        );
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistProductRules(array $validRows): array
    {
        $now = now();
        $payload = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $payload[] = [
                'supplier_id' => $row['supplier_id'],
                'product_id' => $row['product_id'],
                'supplier_sku' => $row['supplier_sku'],
                'moq' => $row['moq'],
                'pack_multiple' => $row['pack_multiple'],
                'pallet_quantity' => $row['pallet_quantity'],
                'min_transport_quantity' => $row['min_transport_quantity'],
                'lead_time_days' => $row['lead_time_days'],
                'safety_days' => $row['safety_days'],
                'safety_rule_type' => $row['safety_rule_type'],
                'transport_rule_type' => $row['transport_rule_type'],
                'order_enabled' => $row['order_enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SupplierProductRule::query()->upsert($payload, ['supplier_id', 'product_id'], [
            'supplier_sku',
            'moq',
            'pack_multiple',
            'pallet_quantity',
            'min_transport_quantity',
            'lead_time_days',
            'safety_days',
            'safety_rule_type',
            'transport_rule_type',
            'order_enabled',
            'updated_at',
        ]);

        $models = SupplierProductRule::query()
            ->select(['id', 'supplier_id', 'product_id'])
            ->whereIn('supplier_id', array_values(array_unique(array_column($payload, 'supplier_id'))))
            ->whereIn('product_id', array_values(array_unique(array_column($payload, 'product_id'))))
            ->get()
            ->keyBy(fn (SupplierProductRule $rule): string => $rule->supplier_id.':'.$rule->product_id);
        $related = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $model = $models->get($row['supplier_id'].':'.$row['product_id']);

            if ($model instanceof SupplierProductRule) {
                $related[(int) $validRow['row_number']] = [
                    'type' => SupplierProductRule::class,
                    'id' => (int) $model->getKey(),
                ];
            }
        }

        return $related;
    }

    /**
     * @param  list<array<string,mixed>>  $validRows
     * @param  array<string, mixed>  $context
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function persistInboundOrders(ImportBatch $batch, array $validRows, array $context): array
    {
        $now = now();
        $orders = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $orders[] = [
                'company_id' => $batch->company_id,
                'supplier_id' => $row['supplier_id'],
                'order_number' => $row['order_number'],
                'supplier_order_reference' => $row['source_reference'],
                'status' => $row['status'],
                'ordered_at' => $now,
                'expected_arrival_date' => $row['expected_arrival_date'],
                'confirmed_arrival_date' => $row['confirmed_arrival_date'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        InboundOrder::query()->insert($orders);

        $orderModels = InboundOrder::query()
            ->select(['id', 'supplier_order_reference'])
            ->where('company_id', $batch->company_id)
            ->whereIn('supplier_order_reference', array_column($orders, 'supplier_order_reference'))
            ->get()
            ->keyBy('supplier_order_reference');
        $items = [];
        $related = [];

        foreach ($validRows as $validRow) {
            $row = $validRow['normalized'];
            $order = $orderModels->get($row['source_reference']);

            if (! $order instanceof InboundOrder) {
                continue;
            }

            $items[] = [
                'inbound_order_id' => $order->getKey(),
                'product_id' => $row['product_id'],
                'ordered_quantity' => $row['ordered_quantity'],
                'confirmed_quantity' => $row['confirmed_quantity'],
                'received_quantity' => null,
                'expected_arrival_date' => $row['expected_arrival_date'],
                'confirmed_arrival_date' => $row['confirmed_arrival_date'],
                'status' => $row['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $related[(int) $validRow['row_number']] = [
                'type' => InboundOrder::class,
                'id' => (int) $order->getKey(),
            ];
        }

        if ($items !== []) {
            InboundOrderItem::query()->insert($items);
        }

        return $related;
    }

    /**
     * @param  iterable<Model>  $models
     * @param  class-string<Model>  $modelClass
     * @param  list<array<string,mixed>>  $validRows
     * @return array<int, array{type:class-string<Model>,id:int}>
     */
    private function relatedMap(iterable $models, string $modelClass, array $validRows): array
    {
        $modelsByReference = collect($models)->keyBy('source_reference');
        $related = [];

        foreach ($validRows as $validRow) {
            $sourceReference = $validRow['normalized']['source_reference'];
            $model = $modelsByReference->get($sourceReference);

            if ($model instanceof Model) {
                $related[(int) $validRow['row_number']] = [
                    'type' => $modelClass,
                    'id' => (int) $model->getKey(),
                ];
            }
        }

        return $related;
    }

    /**
     * @param  array{valid_rows:list<array<string,mixed>>,failed_rows:list<array<string,mixed>>}  $prepared
     * @param  array<int, array{type:class-string<Model>,id:int}>  $relatedModels
     * @return list<array<string,mixed>>
     */
    private function importRowPayload(ImportBatch $batch, array $prepared, array $relatedModels, bool $dryRun, mixed $now): array
    {
        $rows = [];

        foreach ($prepared['valid_rows'] as $validRow) {
            $related = $relatedModels[(int) $validRow['row_number']] ?? null;
            $rows[] = [
                'import_batch_id' => $batch->getKey(),
                'row_number' => $validRow['row_number'],
                'raw_json' => json_encode($validRow['raw'], JSON_THROW_ON_ERROR),
                'normalized_json' => json_encode($validRow['normalized'], JSON_THROW_ON_ERROR),
                'status' => $dryRun ? 'dry_run' : 'successful',
                'error_message' => null,
                'related_model_type' => $related['type'] ?? null,
                'related_model_id' => $related['id'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($prepared['failed_rows'] as $failedRow) {
            $rows[] = [
                'import_batch_id' => $batch->getKey(),
                'row_number' => $failedRow['row_number'],
                'raw_json' => json_encode($failedRow['raw'], JSON_THROW_ON_ERROR),
                'normalized_json' => json_encode($failedRow['normalized'], JSON_THROW_ON_ERROR),
                'status' => 'failed',
                'error_message' => implode(' ', $failedRow['errors']),
                'related_model_type' => null,
                'related_model_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        usort($rows, fn (array $left, array $right): int => $left['row_number'] <=> $right['row_number']);

        return $rows;
    }

    private function rowSourceReference(ImportBatch $batch, int $rowNumber): string
    {
        return 'import_batch_'.$batch->getKey().'_row_'.$rowNumber;
    }

    private function isDuplicateFile(int $companyId, string $importType, string $checksum, mixed $sourceName): bool
    {
        return ImportBatch::query()
            ->where('company_id', $companyId)
            ->where('source_type', $importType)
            ->where('checksum', $checksum)
            ->when($sourceName !== null, fn ($query) => $query->where('source_name', $sourceName))
            ->exists();
    }

    private function finalStatus(bool $dryRun, int $failedRows): string
    {
        if ($dryRun) {
            return ImportBatchStatus::DryRun->value;
        }

        return $failedRows > 0
            ? ImportBatchStatus::CompletedWithErrors->value
            : ImportBatchStatus::Completed->value;
    }

    /**
     * @param  list<array<string,mixed>>  $failedRows
     */
    private function summary(bool $duplicate, array $failedRows): ?string
    {
        $messages = [];

        if ($duplicate) {
            $messages[] = 'duplicate_file_warning';
        }

        if ($failedRows !== []) {
            $messages[] = count($failedRows).' row(s) failed validation';
        }

        return $messages === [] ? null : implode('; ', $messages);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(string $eventType, ImportBatch $batch, array $metadata, mixed $userId): void
    {
        AuditLog::query()->create([
            'company_id' => $batch->company_id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'auditable_type' => $batch->getMorphClass(),
            'auditable_id' => $batch->getKey(),
            'old_values_json' => null,
            'new_values_json' => [
                'status' => $batch->status?->value ?? $batch->status,
                'successful_rows' => $batch->successful_rows,
                'failed_rows' => $batch->failed_rows,
            ],
            'metadata_json' => $metadata,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
        ]);
    }
}
