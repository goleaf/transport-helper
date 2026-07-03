<?php

namespace Database\Factories;

use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderProposalItem>
 */
class OrderProposalItemFactory extends Factory
{
    public function definition(): array
    {
        $t0 = now();

        return [
            'order_proposal_id' => OrderProposal::factory(),
            'product_id' => Product::factory(),
            't0_date' => $t0->toDateString(),
            't1_date' => $t0->copy()->addDays(7)->toDateString(),
            't2_date' => $t0->copy()->addDays(21)->toDateString(),
            't3_date' => $t0->copy()->addDays(35)->toDateString(),
            'trend' => 0,
            'need_t0_t1' => 150,
            'stock_t1' => 0,
            'need_t1_t2' => 150,
            'safety_stock' => 6,
            'inbound_until_t1' => 0,
            'inbound_t1_t3' => 0,
            'reserved_quantity' => 0,
            'raw_need' => 150,
            'moq_applied' => null,
            'pack_multiple_applied' => null,
            'pallet_quantity_applied' => 156,
            'recommended_quantity' => 156,
            'approved_quantity' => null,
            'user_adjusted_quantity' => null,
            'adjustment_reason' => null,
            'explanation_json' => [
                'formula' => 'T3 = round_up(T2 + 4% safety)',
            ],
            'warnings_json' => [],
            'requires_human_review' => false,
            'status' => 'draft',
        ];
    }
}
