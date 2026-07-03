<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class DemoCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()->updateOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Supply Company',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );
    }
}
