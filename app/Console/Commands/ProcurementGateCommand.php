<?php

namespace App\Console\Commands;

use App\Models\CalculationScenario;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\Procurement\ProcurementGateService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ProcurementGateCommand extends Command
{
    protected $signature = 'supply:procurement-gate
                            {type : proposal, supplier_order or scenario}
                            {id : Model ID}
                            {action : Action to check}
                            {--json : Output JSON}';

    protected $description = 'Run a read-only procurement gate check for a proposal, supplier order or scenario.';

    public function handle(ProcurementGateService $gateService): int
    {
        $model = $this->model((string) $this->argument('type'), (int) $this->argument('id'));
        $user = User::query()->select(['id', 'name', 'email', 'role'])->where('role', 'admin')->first()
            ?? User::query()->select(['id', 'name', 'email', 'role'])->firstOrFail();
        $result = $gateService->gate($model, (string) $this->argument('action'), $user);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $result['status'] === 'blocked' ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Procurement gate: '.strtoupper((string) $result['status']));
        $this->line('Action: '.$result['action']);
        $this->line('Mode: '.$result['enforcement_mode']);
        $this->line('Estimated total: '.$result['estimated_value']['total'].' '.$result['estimated_value']['currency']);

        if (($result['blocking_reasons'] ?? []) !== []) {
            $this->warn('Blocking reasons: '.implode(', ', $result['blocking_reasons']));
        }

        if (($result['warnings'] ?? []) !== []) {
            $this->warn('Warnings: '.implode(', ', $result['warnings']));
        }

        return $result['status'] === 'blocked' ? self::FAILURE : self::SUCCESS;
    }

    private function model(string $type, int $id): OrderProposal|SupplierOrder|CalculationScenario
    {
        return match ($type) {
            'proposal' => OrderProposal::query()->findOrFail($id),
            'supplier_order' => SupplierOrder::query()->findOrFail($id),
            'scenario' => CalculationScenario::query()->findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported procurement gate type.'),
        };
    }
}
