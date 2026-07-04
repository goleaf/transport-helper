<?php

namespace Tests\Support;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierProductRule;
use App\Models\User;

class MasterDataTestSupport
{
    /**
     * @return array{company:Company,supplier:Supplier,product:Product,admin:User,user:User,viewer:User,contact:SupplierContact,rule:SupplierProductRule}
     */
    public static function fixture(array $overrides = []): array
    {
        $company = Company::factory()->create(['default_currency' => 'EUR']);
        $supplier = Supplier::factory()->for($company)->create([
            'name' => $overrides['supplier_name'] ?? 'Nordic Parts',
            'code' => $overrides['supplier_code'] ?? 'NORDIC',
        ]);
        $product = Product::factory()->for($company)->create([
            'sku' => $overrides['sku'] ?? 'SKU-1001',
            'manufacturer_sku' => $overrides['manufacturer_sku'] ?? 'MFG-1001',
            'name' => $overrides['product_name'] ?? 'Air filter cartridge',
            'category' => 'filters',
            'brand' => 'Acme',
        ]);
        $contact = SupplierContact::factory()->for($supplier)->create(['email' => 'orders@nordic.test']);
        $rule = SupplierProductRule::factory()->for($supplier)->for($product)->create(['supplier_sku' => 'SUP-1001']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::SupplyManager]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        return compact('company', 'supplier', 'product', 'admin', 'user', 'viewer', 'contact', 'rule');
    }
}
