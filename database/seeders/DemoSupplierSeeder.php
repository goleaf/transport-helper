<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Database\Seeder;

class DemoSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Supply Company',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );

        $manufacturer = Supplier::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'DEMO-MANUFACTURER',
            ],
            [
                'name' => 'Demo Manufacturer',
                'type' => 'manufacturer',
                'default_language' => 'en',
                'default_currency' => 'EUR',
                'default_lead_time_days' => 21,
                'is_active' => true,
                'notes' => 'Demo manufacturer for procurement workflows.',
            ]
        );

        Supplier::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'DEMO-DISTRIBUTOR',
            ],
            [
                'name' => 'Demo Distributor',
                'type' => 'distributor',
                'default_language' => 'en',
                'default_currency' => 'EUR',
                'default_lead_time_days' => 14,
                'is_active' => true,
                'notes' => 'Demo distributor for comparison and fallback procurement.',
            ]
        );

        SupplierContact::query()->updateOrCreate(
            [
                'supplier_id' => $manufacturer->getKey(),
                'email' => 'orders@manufacturer.test',
            ],
            [
                'name' => 'Demo Orders Contact',
                'phone' => '+37060000001',
                'role' => 'Orders',
                'receives_orders' => true,
                'receives_transport_requests' => false,
                'is_active' => true,
            ]
        );

        SupplierContact::query()->updateOrCreate(
            [
                'supplier_id' => $manufacturer->getKey(),
                'email' => 'logistics@manufacturer.test',
            ],
            [
                'name' => 'Demo Logistics Contact',
                'phone' => '+37060000002',
                'role' => 'Logistics',
                'receives_orders' => false,
                'receives_transport_requests' => true,
                'is_active' => true,
            ]
        );
    }
}
