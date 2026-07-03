<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\Company;
use Illuminate\Database\Seeder;

class DemoCarrierSeeder extends Seeder
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

        foreach (['A', 'B', 'C'] as $index => $suffix) {
            $carrier = Carrier::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => 'DEMO-CARRIER-'.$suffix,
                ],
                [
                    'name' => 'Demo Carrier '.$suffix,
                    'default_currency' => 'EUR',
                    'reliability_score' => 95 - ($index * 5),
                    'is_active' => true,
                    'notes' => 'Demo transport carrier '.$suffix.'.',
                ]
            );

            CarrierContact::query()->updateOrCreate(
                [
                    'carrier_id' => $carrier->getKey(),
                    'email' => 'quote-'.strtolower($suffix).'@carrier.test',
                ],
                [
                    'name' => 'Demo Carrier '.$suffix.' Quotes',
                    'phone' => '+3706000000'.($index + 3),
                    'is_active' => true,
                ]
            );
        }
    }
}
