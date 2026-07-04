<?php

namespace App\Services\Supply\MasterData;

use App\Models\Company;
use App\Models\ExportFile;
use App\Models\MasterDataChangeRequest;
use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Models\UnknownSkuResolution;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;

class MasterDataQualityReportService
{
    public function __construct(
        private readonly MasterDataDuplicateDetectionService $duplicateDetectionService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{summary: array<string,int>, issues: list<array<string,mixed>>, duplicate_suggestions: list<array<string,mixed>>}
     */
    public function report(Company $company, array $filters = []): array
    {
        $productDuplicates = $this->duplicateDetectionService->detectProductDuplicates($company, ['limit' => 300]);
        $supplierDuplicates = $this->duplicateDetectionService->detectSupplierDuplicates($company, ['limit' => 300]);
        $skuConflicts = $this->duplicateDetectionService->detectSupplierSkuConflicts($company);
        $issues = [];

        Product::query()
            ->select(['id', 'company_id', 'sku', 'name', 'manufacturer_sku'])
            ->whereBelongsTo($company)
            ->whereNull('manufacturer_sku')
            ->limit(50)
            ->get()
            ->each(function (Product $product) use (&$issues): void {
                $issues[] = [
                    'type' => 'missing_manufacturer_sku',
                    'severity' => 'warning',
                    'object_type' => Product::class,
                    'object_id' => $product->id,
                    'message' => 'Product is missing manufacturer SKU.',
                    'recommended_action' => 'Add manufacturer SKU or approved alias.',
                ];
            });

        Product::query()
            ->select(['id', 'company_id', 'sku', 'name'])
            ->whereBelongsTo($company)
            ->whereDoesntHave('supplierProductRules')
            ->limit(50)
            ->get()
            ->each(function (Product $product) use (&$issues): void {
                $issues[] = [
                    'type' => 'missing_supplier_rule',
                    'severity' => 'warning',
                    'object_type' => Product::class,
                    'object_id' => $product->id,
                    'message' => 'Product has no supplier product rule.',
                    'recommended_action' => 'Create supplier product rule or supplier product identity.',
                ];
            });

        SupplierProductRule::query()
            ->select(['id', 'supplier_id', 'product_id', 'supplier_sku', 'pack_multiple'])
            ->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))
            ->where(function ($query): void {
                $query->whereNull('supplier_sku')->orWhereNull('pack_multiple');
            })
            ->limit(50)
            ->get()
            ->each(function (SupplierProductRule $rule) use (&$issues): void {
                $issues[] = [
                    'type' => 'supplier_rule_incomplete',
                    'severity' => 'warning',
                    'object_type' => SupplierProductRule::class,
                    'object_id' => $rule->id,
                    'message' => 'Supplier product rule is missing supplier SKU or pack multiple.',
                    'recommended_action' => 'Complete supplier product mapping.',
                ];
            });

        return [
            'summary' => [
                'product_count' => Product::query()->whereBelongsTo($company)->count(),
                'active_product_count' => Product::query()->whereBelongsTo($company)->where('is_active', true)->count(),
                'supplier_count' => Supplier::query()->whereBelongsTo($company)->count(),
                'unresolved_unknown_sku_count' => UnknownSkuResolution::query()->whereBelongsTo($company)->unresolved()->count(),
                'pending_change_request_count' => MasterDataChangeRequest::query()->whereBelongsTo($company)->where('status', 'pending_approval')->count(),
                'pending_merge_proposal_count' => MasterDataMergeProposal::query()->whereBelongsTo($company)->where('status', 'pending_approval')->count(),
                'duplicate_product_suggestions_count' => count($productDuplicates),
                'duplicate_supplier_suggestions_count' => count($supplierDuplicates),
                'supplier_sku_conflicts_count' => count($skuConflicts),
            ],
            'issues' => $issues,
            'duplicate_suggestions' => array_merge($productDuplicates, $supplierDuplicates, $skuConflicts),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{export: ExportFile, rows_count: int}
     */
    public function exportCsv(Company $company, array $filters, User $user): array
    {
        $report = $this->report($company, $filters);
        $rows = collect($report['issues'])->map(fn (array $issue): array => [
            $issue['type'],
            $issue['severity'],
            $issue['object_type'],
            $issue['object_id'],
            $issue['message'],
            $issue['recommended_action'],
        ]);
        $csv = "type,severity,object_type,object_id,message,recommended_action\n";
        $csv .= $rows->map(fn (array $row): string => collect($row)
            ->map(fn (mixed $value): string => '"'.str_replace('"', '""', (string) $value).'"')
            ->implode(','))
            ->implode("\n");

        $filename = 'master-data-quality-'.$company->id.'-'.now()->format('Ymd-His').'.csv';
        $path = 'exports/master-data/'.$filename;
        Storage::disk('local')->put($path, $csv);

        $export = ExportFile::query()->create([
            'company_id' => $company->getKey(),
            'export_type' => 'master_data_quality_csv',
            'related_model_type' => Company::class,
            'related_model_id' => $company->getKey(),
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => 'text/csv',
            'status' => 'stored',
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('master_data_quality_report_exported', $export, $user, null, [
            'rows_count' => $rows->count(),
        ], [], $company->getKey());

        return ['export' => $export, 'rows_count' => $rows->count()];
    }
}
