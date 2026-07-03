<?php

namespace Tests\Support;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\ProcurementBudget;
use App\Models\ProcurementBudgetLine;
use App\Models\ProcurementPolicy;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductPrice;
use App\Models\User;

class ProcurementTestSupport
{
    /**
     * @return array{company:Company,supplier:Supplier,product:Product,user:User,manager:User,proposal:OrderProposal,proposalItem:OrderProposalItem,order:SupplierOrder,orderItem:SupplierOrderItem,budget:ProcurementBudget,policy:ProcurementPolicy}
     */
    public static function fixture(array $overrides = []): array
    {
        $company = Company::factory()->create(['default_currency' => 'EUR']);
        $supplier = Supplier::factory()->for($company)->create(['default_currency' => 'EUR']);
        $product = Product::factory()->for($company)->create(['category' => 'filters']);
        $user = User::factory()->create(['role' => UserRole::SupplyManager]);
        $manager = User::factory()->create(['role' => UserRole::Admin]);

        $proposal = OrderProposal::factory()
            ->for($company)
            ->for($supplier)
            ->create(['created_by_user_id' => $user->getKey(), 'total_lines' => 1]);
        $proposalItem = OrderProposalItem::factory()
            ->for($proposal, 'orderProposal')
            ->for($product)
            ->create(['recommended_quantity' => $overrides['proposal_quantity'] ?? 100, 'approved_quantity' => $overrides['approved_quantity'] ?? null]);

        $order = SupplierOrder::factory()
            ->for($company)
            ->for($supplier)
            ->for($proposal, 'orderProposal')
            ->create(['order_date' => '2026-07-04', 'sent_by_user_id' => $user->getKey()]);
        $orderItem = SupplierOrderItem::factory()
            ->for($order, 'supplierOrder')
            ->for($product)
            ->create([
                'ordered_quantity' => $overrides['ordered_quantity'] ?? 100,
                'unit_price' => array_key_exists('order_unit_price', $overrides) ? $overrides['order_unit_price'] : 10,
                'currency' => 'EUR',
            ]);

        $budget = ProcurementBudget::factory()->for($company)->create([
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'currency' => 'EUR',
            'total_amount' => $overrides['budget_total'] ?? 10000,
            'status' => 'active',
            'created_by_user_id' => $manager->getKey(),
        ]);

        ProcurementBudgetLine::factory()->for($budget, 'budget')->create([
            'supplier_id' => $supplier->getKey(),
            'product_id' => null,
            'category' => null,
            'amount' => $overrides['budget_line_amount'] ?? 10000,
        ]);

        $policy = ProcurementPolicy::factory()->for($company)->create([
            'name' => 'Default procurement policy',
            'enforcement_mode' => $overrides['enforcement_mode'] ?? 'advisory',
            'is_default' => true,
            'rules_json' => $overrides['rules_json'] ?? ['missing_price_requires_approval' => true],
            'approval_thresholds_json' => $overrides['approval_thresholds_json'] ?? [],
            'supplier_rules_json' => $overrides['supplier_rules_json'] ?? [],
            'budget_rules_json' => $overrides['budget_rules_json'] ?? [],
            'created_by_user_id' => $manager->getKey(),
        ]);

        return [
            'company' => $company,
            'supplier' => $supplier,
            'product' => $product,
            'user' => $user,
            'manager' => $manager,
            'proposal' => $proposal,
            'proposalItem' => $proposalItem,
            'order' => $order,
            'orderItem' => $orderItem,
            'budget' => $budget,
            'policy' => $policy,
        ];
    }

    public static function price(Company $company, Supplier $supplier, Product $product, float $unitPrice = 10.0): SupplierProductPrice
    {
        return SupplierProductPrice::factory()
            ->for($company)
            ->for($supplier)
            ->for($product)
            ->create([
                'unit_price' => $unitPrice,
                'currency' => 'EUR',
                'valid_from' => '2026-01-01',
                'valid_to' => null,
                'status' => 'active',
            ]);
    }
}
