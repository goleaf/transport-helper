<?php

namespace App\Services\Supply\Pilot;

use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class PilotUatChecklistService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultChecklist(): array
    {
        return [
            $this->item('data_import', 'sales_import_dry_run', 'Sales sample imported in dry-run', 'supply_manager', true),
            $this->item('data_import', 'stock_import_dry_run', 'Stock sample imported in dry-run', 'supply_manager', true),
            $this->item('data_import', 'product_rules_mapped', 'Product rules mapped', 'supply_manager', true),
            $this->item('data_import', 'unknown_sku_report_reviewed', 'Unknown SKU report reviewed', 'supply_manager', true),
            $this->item('data_import', 'date_quantity_parsing_reviewed', 'Date and quantity parsing reviewed', 'supply_manager', true),
            $this->item('calculation', 'calculation_run_created', 'Calculation run created', 'supply_manager', true),
            $this->item('calculation', 'formula_explanation_visible', 'Formula explanation visible', 'supply_manager', true),
            $this->item('calculation', 'regression_150_156_passes', '150 to 156 regression test passes', 'supply_manager', true),
            $this->item('order_proposal', 'item_approval_works', 'Item approval works', 'supply_manager', true),
            $this->item('order_proposal', 'adjustment_requires_reason', 'Adjustment requires reason', 'supply_manager', true),
            $this->item('supplier_order', 'manufacturer_form_preview_works', 'Manufacturer form preview works', 'supply_manager', true),
            $this->item('supplier_order', 'email_cannot_send_without_approval', 'Email cannot send without approval', 'supply_manager', true),
            $this->item('inbound_email', 'supplier_email_sample_ingested', 'Supplier confirmation email sample ingested', 'supply_manager', true),
            $this->item('inbound_email', 'ai_acceptance_does_not_apply', 'AI extraction acceptance does not apply automatically', 'supply_manager', true),
            $this->item('form_autofill', 'low_confidence_blocks_validation', 'Low confidence blocks validation', 'supply_manager', true),
            $this->item('form_autofill', 'validated_run_applied_by_service_only', 'Validated run can be applied only through service', 'supply_manager', true),
            $this->item('confirmation', 'confirmation_application_checked', 'Confirmation application checked', 'supply_manager', true),
            $this->item('confirmation', 'quantity_mismatch_visible', 'Quantity mismatch visible', 'supply_manager', true),
            $this->item('transport', 'carrier_quote_sample_processed', 'Carrier quote sample processed', 'logistics_manager', true),
            $this->item('transport', 'lowest_price_not_auto_selected', 'Lowest price is not automatically selected', 'logistics_manager', true),
            $this->item('logistics_receiving', 'logistics_dashboard_shows_record', 'Logistics dashboard shows record', 'logistics_manager', true),
            $this->item('logistics_receiving', 'goods_receipt_mismatch_notification', 'Goods receipt mismatch notification works', 'logistics_manager', true),
            $this->item('security_operations', 'roles_verified', 'Roles verified', 'admin', true),
            $this->item('security_operations', 'audit_events_visible', 'Audit events visible', 'admin', true),
            $this->item('security_operations', 'backup_health_reviewed', 'Backup and health checks reviewed', 'admin', true),
            $this->item('security_operations', 'no_secrets_committed', 'No secrets committed', 'admin', true),
            $this->item('security_operations', 'external_integrations_reviewed', 'External integrations approved or disabled', 'admin', true),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getChecklist(PilotSupplier $pilot): array
    {
        return $pilot->uat_checklist_json ?: $this->defaultChecklist();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function updateChecklist(PilotSupplier $pilot, array $items, User $user): array
    {
        $existing = collect($this->getChecklist($pilot))->keyBy('key');
        $updated = [];

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            $base = $existing->get($key);

            if (! $base) {
                continue;
            }

            $status = (string) ($item['status'] ?? $base['status']);
            $note = $item['note'] ?? $base['note'] ?? null;
            $evidence = $item['evidence'] ?? $base['evidence'] ?? null;

            if (! in_array($status, ['pending', 'passed', 'failed', 'blocked', 'not_applicable'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Invalid UAT checklist status.',
                ]);
            }

            if ($status === 'not_applicable' && trim((string) $note) === '') {
                throw ValidationException::withMessages([
                    'items' => 'Not applicable critical items require a note.',
                ]);
            }

            $base['status'] = $status;
            $base['note'] = $note;
            $base['evidence'] = $evidence;
            $base['updated_by_user_id'] = $user->id;
            $base['updated_at'] = now()->toISOString();
            $updated[$key] = $base;
        }

        $checklist = $existing->map(fn (array $item, string $key): array => $updated[$key] ?? $item)->values()->all();
        $pilot->update(['uat_checklist_json' => $checklist]);

        $evaluation = $this->evaluate($pilot->fresh());

        $this->auditLogService->write('pilot_uat_checklist_updated', $pilot, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'updated_count' => count($updated),
            'live_ready' => $evaluation['live_ready'],
        ], $pilot->company_id);

        return [
            'pilot' => $pilot->fresh(),
            'checklist' => $checklist,
            'evaluation' => $evaluation,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function markItem(PilotSupplier $pilot, string $itemKey, string $status, ?string $note, User $user): array
    {
        return $this->updateChecklist($pilot, [[
            'key' => $itemKey,
            'status' => $status,
            'note' => $note,
        ]], $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(PilotSupplier $pilot): array
    {
        $items = collect($this->getChecklist($pilot));
        $criticalItems = $items->where('critical', true);
        $blocking = $criticalItems->filter(function (array $item): bool {
            $status = (string) ($item['status'] ?? 'pending');

            if ($status === 'passed') {
                return false;
            }

            return ! ($status === 'not_applicable' && trim((string) ($item['note'] ?? '')) !== '');
        })->values();

        return [
            'total_items' => $items->count(),
            'critical_items' => $criticalItems->count(),
            'passed_count' => $items->where('status', 'passed')->count(),
            'failed_count' => $items->whereIn('status', ['failed', 'blocked'])->count(),
            'pending_critical_count' => $blocking->count(),
            'live_ready' => $blocking->isEmpty(),
            'blocking_items' => $blocking->pluck('key')->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $section, string $key, string $label, string $owner, bool $critical): array
    {
        return [
            'section' => $section,
            'key' => $key,
            'label' => $label,
            'status' => 'pending',
            'owner' => $owner,
            'critical' => $critical,
            'note' => null,
            'evidence' => null,
            'updated_by_user_id' => null,
            'updated_at' => null,
        ];
    }
}
