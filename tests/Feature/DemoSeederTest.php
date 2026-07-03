<?php

use App\Models\Carrier;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds demo supply data and remains idempotent', function () {
    $this->seed(DatabaseSeeder::class);

    $countsAfterFirstRun = [
        'roles' => Role::query()->count(),
        'templates' => FormTemplate::query()->count(),
        'products' => Product::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    $company = Company::query()->where('code', 'DEMO')->firstOrFail();

    expect($company->name)->toBe('Demo Supply Company')
        ->and(Supplier::query()->where('company_id', $company->getKey())->where('name', 'Demo Manufacturer')->exists())->toBeTrue()
        ->and(Carrier::query()->where('company_id', $company->getKey())->where('name', 'Demo Carrier A')->exists())->toBeTrue()
        ->and(Product::query()->where('company_id', $company->getKey())->count())->toBeGreaterThanOrEqual(5)
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'supplier_confirmation_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'carrier_quote_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'logistics_update_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('code', 'supplier_confirmation_v1')->firstOrFail()->fields()->where('field_key', 'confirmed_quantity')->exists())->toBeTrue()
        ->and($countsAfterFirstRun['roles'])->toBe(Role::query()->count())
        ->and($countsAfterFirstRun['templates'])->toBe(FormTemplate::query()->count())
        ->and($countsAfterFirstRun['products'])->toBe(Product::query()->count());
});
