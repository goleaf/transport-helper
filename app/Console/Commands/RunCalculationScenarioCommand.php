<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Supply\Forecasting\ScenarioSimulationService;
use Illuminate\Console\Command;

class RunCalculationScenarioCommand extends Command
{
    protected $signature = 'supply:run-scenario
                            {--company_id= : Company ID}
                            {--supplier_id= : Supplier ID}
                            {--name= : Scenario name}
                            {--product_ids= : Comma-separated product IDs}
                            {--category= : Product category}
                            {--t0= : T0 date}
                            {--t1= : T1 date}
                            {--t2= : T2 date}
                            {--t3= : T3 date}
                            {--json : Output JSON}';

    protected $description = 'Run a deterministic replenishment calculation scenario without mutating business records.';

    public function handle(ScenarioSimulationService $service): int
    {
        $missing = collect(['company_id', 'supplier_id', 'name', 't0', 't1', 't2', 't3'])
            ->filter(fn (string $option): bool => blank($this->option($option)))
            ->values()
            ->all();

        if ($missing !== []) {
            $this->error('Missing required options: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $company = Company::query()->select(['id', 'name'])->find((int) $this->option('company_id'));
        $supplier = Supplier::query()->select(['id', 'company_id', 'name', 'code', 'default_lead_time_days'])->find((int) $this->option('supplier_id'));
        $user = User::query()->select(['id', 'name', 'email', 'role'])->where('role', 'admin')->first()
            ?? User::query()->select(['id', 'name', 'email', 'role'])->first();

        if (! $company instanceof Company || ! $supplier instanceof Supplier || ! $user instanceof User) {
            $this->error('Company, supplier or user was not found.');

            return self::FAILURE;
        }

        $parameters = [
            'company_id' => $company->getKey(),
            'supplier_id' => $supplier->getKey(),
            'name' => (string) $this->option('name'),
            'product_ids' => $this->productIds(),
            'category' => $this->option('category'),
            't0_date' => (string) $this->option('t0'),
            't1_date' => (string) $this->option('t1'),
            't2_date' => (string) $this->option('t2'),
            't3_date' => (string) $this->option('t3'),
            'scenario_options' => [
                'exclude_promotions' => true,
                'exclude_anomalies' => true,
                'use_manual_overrides' => true,
            ],
        ];

        $result = $service->simulate($company, $supplier, $parameters, $user);
        $scenario = $result['scenario'];

        if ($this->option('json')) {
            $this->line(json_encode([
                'id' => $scenario->getKey(),
                'name' => $scenario->name,
                'status' => $scenario->status?->value ?? $scenario->status,
                'summary' => $scenario->summary_json,
                'warnings' => $scenario->warnings_json,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Scenario simulated: '.$scenario->name);
        $this->table(
            ['ID', 'Status', 'Items', 'Needs review', 'Total quantity'],
            [[
                $scenario->getKey(),
                $scenario->status?->value ?? $scenario->status,
                $scenario->summary_json['items_count'] ?? 0,
                $scenario->summary_json['needs_review_count'] ?? 0,
                $scenario->summary_json['total_simulated_quantity'] ?? 0,
            ]],
        );

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function productIds(): array
    {
        return collect(explode(',', (string) $this->option('product_ids')))
            ->filter()
            ->map(fn (string $id): int => (int) trim($id))
            ->values()
            ->all();
    }
}
