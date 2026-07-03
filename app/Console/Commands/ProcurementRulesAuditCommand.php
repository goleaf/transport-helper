<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ProcurementApprovalRequest;
use App\Models\ProcurementBudget;
use App\Models\ProcurementException;
use App\Models\ProcurementPolicy;
use App\Models\SupplierProductPrice;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ProcurementRulesAuditCommand extends Command
{
    protected $signature = 'supply:procurement-rules-audit
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Audit procurement rules, budgets, prices and safety boundaries.';

    public function handle(AuditLogService $auditLogService): int
    {
        $checks = [
            $this->check('active_policies', ProcurementPolicy::query()->where('status', 'active')->count(), 'Active procurement policies exist.', warningWhenZero: true),
            $this->check('default_policies', ProcurementPolicy::query()->where('status', 'active')->where('is_default', true)->count(), 'Default procurement policies exist.', warningWhenZero: true),
            $this->check('active_budgets', ProcurementBudget::query()->where('status', 'active')->count(), 'Active procurement budgets exist.', warningWhenZero: true),
            $this->check('supplier_product_prices', SupplierProductPrice::query()->where('status', 'active')->count(), 'Active supplier product prices exist.', warningWhenZero: true),
            $this->check('pending_approvals', ProcurementApprovalRequest::query()->where('status', 'pending')->count(), 'Pending approvals.'),
            $this->check('pending_exceptions', ProcurementException::query()->where('status', 'pending')->count(), 'Pending exceptions.'),
            $this->boundaryCheck(),
            $this->noDtoCheck(),
            $this->autoApprovalConfigCheck(),
        ];
        $warnings = collect($checks)->filter(fn (array $check): bool => $check['status'] === 'warning')->values()->all();
        $status = $warnings === [] ? 'ok' : 'warning';
        $result = ['status' => $status, 'checks' => $checks];

        $auditLogService->write(
            'procurement_rules_audit_run',
            null,
            User::query()->select(['id', 'name', 'email', 'role'])->where('role', 'admin')->first(),
            null,
            $result,
            [],
            Company::query()->value('id'),
        );

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($status);
        }

        $this->info('Procurement rules audit: '.strtoupper($status));
        $this->table(
            ['Name', 'Status', 'Value', 'Message'],
            collect($checks)->map(fn (array $check): array => [
                $check['name'],
                strtoupper($check['status']),
                $check['value'],
                $check['message'],
            ])->all(),
        );

        return $this->exitCode($status);
    }

    private function check(string $name, int $value, string $message, bool $warningWhenZero = false): array
    {
        return [
            'name' => $name,
            'status' => $warningWhenZero && $value === 0 ? 'warning' : 'ok',
            'value' => $value,
            'message' => $message,
        ];
    }

    private function boundaryCheck(): array
    {
        $forbidden = [
            'OpenAI',
            'Http::',
            'Guzzle',
            'EmailSenderInterface',
            'CarrierSelectionService',
            'SupplierOrderSendService',
            'SupplierOrderCreationService',
        ];
        $source = collect(glob(app_path('Services/Supply/Procurement/*.php')) ?: [])
            ->map(fn (string $file): string => file_get_contents($file) ?: '')
            ->implode("\n");
        $found = collect($forbidden)->filter(fn (string $needle): bool => str_contains($source, $needle))->values()->all();

        return [
            'name' => 'procurement_boundary',
            'status' => $found === [] ? 'ok' : 'warning',
            'value' => count($found),
            'message' => $found === [] ? 'No AI, external, email or carrier dependencies found.' : 'Forbidden dependencies found: '.implode(', ', $found),
        ];
    }

    private function noDtoCheck(): array
    {
        $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->map(fn (SplFileInfo $file): string => $file->getPathname());
        $matches = $files->filter(fn (string $path): bool => preg_match('/(?:DTO|Dto)\.php$/', $path) === 1)->values()->all();

        return [
            'name' => 'no_dto',
            'status' => is_dir(app_path('Data')) || $matches !== [] ? 'warning' : 'ok',
            'value' => count($matches),
            'message' => is_dir(app_path('Data')) ? 'app/Data exists.' : 'No DTO files found.',
        ];
    }

    private function autoApprovalConfigCheck(): array
    {
        $allowSelfApproval = (bool) config('supply.procurement.allow_self_approval', false);

        return [
            'name' => 'dangerous_auto_approval_config',
            'status' => $allowSelfApproval ? 'warning' : 'ok',
            'value' => $allowSelfApproval ? 1 : 0,
            'message' => $allowSelfApproval ? 'Self approval is enabled; review before production.' : 'Self approval is disabled.',
        ];
    }

    private function exitCode(string $status): int
    {
        if ($this->option('strict') && $status !== 'ok') {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
