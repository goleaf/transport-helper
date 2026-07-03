<?php

namespace App\Services\Supply\Integrations;

use App\Models\CarrierContact;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Models\ImportBatch;
use App\Models\IntegrationConnection;
use App\Models\SupplierConfirmation;
use App\Models\SupplierContact;
use App\Services\Audit\AuditLogService;

class IntegrationOnboardingChecklistService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $companyId = $options['company_id'] ?? null;
        $items = [
            $this->item('supplier_contacts', SupplierContact::query()->when($companyId, fn ($query) => $query->whereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('company_id', $companyId)))->exists(), 'Supplier contacts configured.'),
            $this->item('manufacturer_forms', FormTemplate::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->whereIn('format_type', ['excel', 'csv', 'pdf', 'portal_manual'])->exists(), 'At least one manufacturer form template exists.'),
            $this->item('sales_csv_sample', ImportBatch::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->where('import_type', 'sales_history')->exists(), 'Sales CSV sample imported.'),
            $this->item('stock_csv_sample', ImportBatch::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->where('import_type', 'stock_snapshot')->exists(), 'Stock CSV sample imported.'),
            $this->item('inbound_email_sample', EmailMessage::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->exists(), 'Inbound email sample processed.'),
            $this->item('supplier_confirmation_sample', SupplierConfirmation::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->exists(), 'Supplier confirmation sample applied.'),
            $this->item('carrier_contacts', CarrierContact::query()->when($companyId, fn ($query) => $query->whereHas('carrier', fn ($carrierQuery) => $carrierQuery->where('company_id', $companyId)))->exists(), 'Carrier contacts configured.'),
            $this->item('external_integrations_reviewed', IntegrationConnection::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->whereNotNull('approval_status')->exists(), 'Integration approval review recorded.'),
        ];

        $status = collect($items)->contains(fn (array $item): bool => $item['status'] === 'error')
            ? 'error'
            : (collect($items)->contains(fn (array $item): bool => $item['status'] === 'warning') ? 'warning' : 'ok');

        $this->auditLogService->write('onboarding_checklist_run', null, null, null, null, [
            'status' => $status,
            'company_id' => $companyId,
            'item_count' => count($items),
            'warning_count' => collect($items)->where('status', 'warning')->count(),
        ], is_numeric($companyId) ? (int) $companyId : null);

        return [
            'status' => $status,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function item(string $key, bool $passes, string $message): array
    {
        return [
            'key' => $key,
            'label' => ucfirst(str_replace('_', ' ', $key)),
            'status' => $passes ? 'ok' : 'warning',
            'message' => $passes ? $message : str_replace('configured', 'missing', $message),
        ];
    }
}
