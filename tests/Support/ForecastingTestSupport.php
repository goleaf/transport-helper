<?php

namespace Tests\Support;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Models\User;

class ForecastingTestSupport
{
    /**
     * @return array{company:Company,supplier:Supplier,product:Product,user:User}
     */
    public static function fixture(array $productOverrides = []): array
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->for($company)->create(['default_lead_time_days' => 21]);
        $product = Product::factory()->for($company)->create($productOverrides + ['category' => 'filters']);
        $user = User::factory()->create(['role' => 'admin']);

        SupplierProductRule::factory()->for($supplier)->for($product)->create([
            'pack_multiple' => 12,
            'pallet_quantity' => 144,
            'lead_time_days' => 21,
            'safety_days' => 14,
        ]);

        StockSnapshot::factory()->for($company)->for($product)->create([
            'snapshot_date' => '2026-07-01',
            'free_stock' => 70,
        ]);

        return [
            'company' => $company,
            'supplier' => $supplier,
            'product' => $product,
            'user' => $user,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function parameters(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Forecast test scenario',
            't0_date' => '2026-07-01',
            't1_date' => '2026-07-15',
            't2_date' => '2026-08-14',
            't3_date' => '2026-09-01',
            'trend_current_start' => '2026-04-01',
            'trend_current_end' => '2026-07-01',
            'trend_last_start' => '2025-04-01',
            'trend_last_end' => '2025-07-01',
            'last_year_t0_t1_start' => '2025-07-01',
            'last_year_t0_t1_end' => '2025-07-15',
            'last_year_t1_t2_start' => '2025-07-15',
            'last_year_t1_t2_end' => '2025-08-14',
            'last_year_t2_t3_start' => '2025-08-14',
            'last_year_t2_t3_end' => '2025-09-01',
            'scenario_options' => [
                'exclude_promotions' => true,
                'exclude_anomalies' => true,
                'use_manual_overrides' => true,
            ],
        ], $overrides);
    }

    public static function seedCalculationSales(Company $company, Product $product): void
    {
        foreach ([
            ['2026-06-01', 120, false, false],
            ['2025-06-01', 100, false, false],
            ['2025-07-10', 40, false, false],
            ['2025-07-20', 100, false, false],
            ['2025-08-20', 60, false, false],
        ] as [$date, $quantity, $promotion, $anomaly]) {
            SalesHistory::factory()->for($company)->for($product)->create([
                'sales_date' => $date,
                'quantity' => $quantity,
                'is_promotion' => $promotion,
                'is_anomaly' => $anomaly,
            ]);
        }
    }

    public static function seedMonthlyHistory(Company $company, Product $product): void
    {
        for ($month = 1; $month <= 12; $month++) {
            SalesHistory::factory()->for($company)->for($product)->create([
                'sales_date' => sprintf('2025-%02d-10', $month),
                'quantity' => $month === 7 ? 220 : 100,
            ]);
        }
    }
}
