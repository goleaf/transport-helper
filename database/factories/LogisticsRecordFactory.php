<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Company;
use App\Models\LogisticsRecord;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogisticsRecord>
 */
class LogisticsRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_order_id' => SupplierOrder::factory(),
            'supplier_id' => Supplier::factory(),
            'carrier_id' => Carrier::factory(),
            'supplier_confirmation_id' => null,
            'selected_carrier_quote_id' => null,
            'order_date' => now()->toDateString(),
            'confirmation_date' => now()->addDays(1)->toDateString(),
            'ready_date' => now()->addDays(10)->toDateString(),
            'pickup_date' => now()->addDays(12)->toDateString(),
            'delivery_date' => now()->addDays(17)->toDateString(),
            'actual_received_date' => null,
            'transport_price' => fake()->randomFloat(3, 100, 2500),
            'currency' => 'EUR',
            'status' => 'planned',
            'external_sheet_reference' => fake()->optional()->uuid(),
            'receiving_discrepancies_json' => null,
            'received_by_user_id' => null,
            'received_at' => null,
            'last_delay_checked_at' => null,
            'delay_reason' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
