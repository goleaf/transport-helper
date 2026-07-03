<?php

namespace App\Services\Supply\Procurement;

use App\Models\Company;
use App\Models\ExportFile;
use App\Models\ProcurementApprovalRequest;
use App\Models\ProcurementBudget;
use App\Models\ProcurementException;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcurementReportService
{
    public function __construct(
        private readonly BudgetAvailabilityService $budgetAvailabilityService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function budgetStatus(array $filters = []): array
    {
        $company = $this->company($filters);
        $budgets = ProcurementBudget::query()
            ->select(['id', 'company_id', 'name', 'period_type', 'date_from', 'date_to', 'currency', 'total_amount', 'status', 'owner_user_id'])
            ->with(['lines:id,procurement_budget_id,supplier_id,product_id,category,amount,committed_amount,spent_amount'])
            ->where('company_id', $company->getKey())
            ->latest('date_from')
            ->limit(50)
            ->get();

        $rows = $budgets->map(fn (ProcurementBudget $budget): array => [
            'budget_id' => $budget->getKey(),
            'name' => $budget->name,
            'period' => $budget->date_from?->toDateString().' - '.$budget->date_to?->toDateString(),
            'currency' => $budget->currency,
            'total_amount' => (float) $budget->total_amount,
            'line_amount' => (float) $budget->lines->sum(fn ($line): float => (float) $line->amount),
            'status' => $budget->status?->value ?? $budget->status,
        ])->values()->all();

        return [
            'summary' => [
                'budgets_count' => $budgets->count(),
                'total_amount' => round($budgets->sum(fn (ProcurementBudget $budget): float => (float) $budget->total_amount), 4),
            ],
            'rows' => $rows,
            'warnings' => $budgets->isEmpty() ? ['no_budgets'] : [],
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function approvalsReport(array $filters = []): array
    {
        $requests = ProcurementApprovalRequest::query()
            ->select(['id', 'company_id', 'approvable_type', 'approvable_id', 'status', 'requested_by_user_id', 'required_role', 'required_permission', 'amount', 'currency', 'reason', 'created_at'])
            ->with('requestedBy:id,name')
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->latest('id')
            ->limit(100)
            ->get();

        return [
            'summary' => [
                'requests_count' => $requests->count(),
                'pending_count' => $requests
                    ->filter(fn (ProcurementApprovalRequest $request): bool => ($request->status?->value ?? $request->status) === 'pending')
                    ->count(),
            ],
            'rows' => $requests->map(fn (ProcurementApprovalRequest $request): array => [
                'id' => $request->getKey(),
                'status' => $request->status?->value ?? $request->status,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'required_role' => $request->required_role,
                'required_permission' => $request->required_permission,
                'requested_by' => $request->requestedBy?->name,
                'reason' => $request->reason,
            ])->values()->all(),
            'warnings' => [],
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exceptionsReport(array $filters = []): array
    {
        $exceptions = ProcurementException::query()
            ->select(['id', 'company_id', 'exception_type', 'exceptable_type', 'exceptable_id', 'status', 'reason', 'requested_by_user_id', 'approved_by_user_id', 'approved_at', 'rejected_at', 'created_at'])
            ->with(['requestedBy:id,name', 'approvedBy:id,name'])
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->latest('id')
            ->limit(100)
            ->get();

        return [
            'summary' => [
                'exceptions_count' => $exceptions->count(),
                'pending_count' => $exceptions
                    ->filter(fn (ProcurementException $exception): bool => ($exception->status?->value ?? $exception->status) === 'pending')
                    ->count(),
            ],
            'rows' => $exceptions->map(fn (ProcurementException $exception): array => [
                'id' => $exception->getKey(),
                'type' => $exception->exception_type?->value ?? $exception->exception_type,
                'status' => $exception->status?->value ?? $exception->status,
                'reason' => $exception->reason,
                'requested_by' => $exception->requestedBy?->name,
                'approved_by' => $exception->approvedBy?->name,
            ])->values()->all(),
            'warnings' => [],
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function supplierSpendReport(array $filters = []): array
    {
        $orders = SupplierOrder::query()
            ->select(['id', 'company_id', 'supplier_id', 'status', 'order_date'])
            ->with(['supplier:id,name', 'items:id,supplier_order_id,ordered_quantity,unit_price,currency'])
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->latest('id')
            ->limit(500)
            ->get();

        $rows = $orders
            ->groupBy('supplier_id')
            ->map(function ($supplierOrders): array {
                $first = $supplierOrders->first();

                return [
                    'supplier_id' => $first?->supplier_id,
                    'supplier' => $first?->supplier?->name,
                    'orders_count' => $supplierOrders->count(),
                    'estimated_spend' => round($supplierOrders->sum(fn (SupplierOrder $order): float => $order->items->sum(fn ($item): float => (float) $item->ordered_quantity * (float) ($item->unit_price ?? 0))), 4),
                ];
            })
            ->values()
            ->all();

        return [
            'summary' => [
                'suppliers_count' => count($rows),
                'estimated_spend' => round(collect($rows)->sum('estimated_spend'), 4),
            ],
            'rows' => $rows,
            'warnings' => [],
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{export_file: ExportFile, path: string, filename: string}
     */
    public function exportCsv(string $reportType, array $filters, User $user): array
    {
        $report = match ($reportType) {
            'approvals' => $this->approvalsReport($filters),
            'exceptions' => $this->exceptionsReport($filters),
            'supplier_spend' => $this->supplierSpendReport($filters),
            default => $this->budgetStatus($filters),
        };
        $rows = $report['rows'] ?: [['message' => 'No rows available']];
        $headers = array_keys($rows[0]);
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (mixed $value): mixed => is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : $value, array_values($row)));
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        $filename = $reportType.'-'.now()->format('Ymd-His').'-'.Str::random(6).'.csv';
        $path = 'exports/procurement/'.$reportType.'/'.$filename;
        Storage::disk('local')->put($path, $contents);
        $companyId = $filters['company_id'] ?? Company::query()->select(['id'])->value('id');
        $export = ExportFile::query()->create([
            'company_id' => $companyId,
            'export_type' => 'procurement_'.$reportType.'_csv',
            'related_model_type' => null,
            'related_model_id' => null,
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => 'text/csv',
            'status' => 'stored',
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->logExport($export, 'procurement_report_exported', $user, [
            'report_type' => $reportType,
        ]);

        return ['export_file' => $export, 'path' => $path, 'filename' => $filename];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function company(array $filters): Company
    {
        return isset($filters['company_id'])
            ? Company::query()->select(['id', 'name'])->findOrFail($filters['company_id'])
            : Company::query()->select(['id', 'name'])->firstOrFail();
    }
}
