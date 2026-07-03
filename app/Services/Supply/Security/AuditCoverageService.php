<?php

namespace App\Services\Supply\Security;

use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class AuditCoverageService
{
    /**
     * @return list<string>
     */
    public function expectedEvents(): array
    {
        return [
            'import_started',
            'import_completed',
            'calculation_run_completed',
            'order_proposal_created',
            'order_proposal_item_calculated',
            'order_quantity_approved',
            'order_quantity_adjusted',
            'order_quantity_rejected',
            'order_proposal_approved',
            'supplier_order_created',
            'supplier_order_exported',
            'supplier_email_draft_prepared',
            'supplier_email_approved',
            'supplier_email_sent',
            'email_received',
            'ai_extraction_created',
            'ai_extraction_accepted',
            'form_autofill_created',
            'form_autofill_field_edited',
            'form_autofill_run_validated',
            'supplier_confirmation_applied',
            'carrier_quote_created',
            'carrier_quote_scored',
            'carrier_selected',
            'goods_receipt_recorded',
            'logistics_record_updated',
            'notification_created',
            'health_check_run',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $source = $this->projectSource();
        $missingEventReferences = collect($this->expectedEvents())
            ->reject(fn (string $event): bool => str_contains($source, $event))
            ->values()
            ->all();
        $missingServiceAuditReferences = $this->missingServiceAuditReferences();

        $checks = [
            $this->check('audit_logs_table', Schema::hasTable('audit_logs') ? 'ok' : 'error', Schema::hasTable('audit_logs') ? 'audit_logs table exists.' : 'audit_logs table is missing.'),
            $this->check('expected_event_references', $missingEventReferences === [] ? 'ok' : 'warning', $missingEventReferences === [] ? 'Expected audit events are referenced.' : 'Some expected audit events are not referenced.', ['missing' => $missingEventReferences]),
            $this->check('critical_service_audit_references', $missingServiceAuditReferences === [] ? 'ok' : 'warning', $missingServiceAuditReferences === [] ? 'Critical services reference audit logging.' : 'Some critical services do not reference audit logging.', ['missing' => $missingServiceAuditReferences]),
        ];

        return [
            'status' => $this->statusFromChecks($checks),
            'checks' => $checks,
            'expected_events' => $this->expectedEvents(),
            'missing_event_references' => $missingEventReferences,
            'missing_service_audit_references' => $missingServiceAuditReferences,
        ];
    }

    private function projectSource(): string
    {
        return collect(['app', 'tests', 'docs'])
            ->flatMap(fn (string $directory): array => $this->files(base_path($directory)))
            ->map(fn (string $file): string => file_get_contents($file) ?: '')
            ->implode("\n");
    }

    /**
     * @return list<string>
     */
    private function missingServiceAuditReferences(): array
    {
        $services = [
            'app/Services/Import/ImportBatchService.php',
            'app/Services/Supply/Calculation/OrderProposalGenerationService.php',
            'app/Services/Supply/OrderProposals/OrderProposalDecisionService.php',
            'app/Services/Supply/SupplierOrders/SupplierOrderSendService.php',
            'app/Services/AI/Email/AiEmailExtractionReviewService.php',
            'app/Services/Forms/FormAutofillReviewService.php',
            'app/Services/Supply/Confirmations/SupplierConfirmationApplicationService.php',
            'app/Services/Supply/Transport/CarrierSelectionService.php',
            'app/Services/Supply/Logistics/LogisticsReceivingService.php',
        ];

        return collect($services)
            ->filter(function (string $path): bool {
                $absolute = base_path($path);

                if (! is_file($absolute)) {
                    return false;
                }

                $source = file_get_contents($absolute) ?: '';

                return ! str_contains($source, 'AuditLogService') && ! str_contains($source, 'auditLogService');
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function files(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['php', 'md'], true))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function check(string $name, string $status, string $message, array $metadata = []): array
    {
        return compact('name', 'status', 'message', 'metadata');
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function statusFromChecks(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }
}
