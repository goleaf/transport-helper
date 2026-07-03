<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Support\SupplyNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('supply dashboard shows requested navigation and priority sections', function () {
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $response = $this
        ->actingAs($user)
        ->get(route('supply.dashboard'));

    $response
        ->assertOk()
        ->assertSeeText('Supply Dashboard')
        ->assertSeeText('Replenishment Priorities')
        ->assertSeeText('Latest Calculation Runs')
        ->assertSeeText('Proposals Needing Review')
        ->assertSeeText('Supplier Orders Awaiting Action')
        ->assertSeeText('Emails Needing Review')
        ->assertSeeText('Form Autofill Runs Needing Review')
        ->assertSeeText('Logistics Delays');

    foreach (SupplyNavigation::items() as $item) {
        $response->assertSeeText($item['label']);
    }
});

test('placeholder supply sections render through named routes', function () {
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $routes = [
        'supply.products.index' => 'Products',
        'supply.suppliers.index' => 'Suppliers',
        'supply.stock.index' => 'Stock',
        'supply.sales-history.index' => 'Sales History',
        'supply.inbound-orders.index' => 'Inbound Orders',
        'supply.reservations.index' => 'Reservations',
        'supply.calculations.index' => 'Calculations',
        'supply.ai-extractions.index' => 'AI Extractions',
        'supply.form-autofill-runs.index' => 'Form Autofill Runs',
        'supply.supplier-confirmations.index' => 'Supplier Confirmations',
        'supply.exports.index' => 'Exports',
        'supply.audit-logs.index' => 'Audit Logs',
        'supply.settings.index' => 'Settings',
        'supply.integrations.index' => 'Integrations',
    ];

    foreach ($routes as $routeName => $label) {
        $this
            ->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSeeText($label)
            ->assertSeeText('Supply Dashboard')
            ->assertSeeText('Order Proposals');
    }
});

test('existing supply pages include the shared navigation', function () {
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this
        ->actingAs($user)
        ->get(route('supply.imports.index'))
        ->assertOk()
        ->assertSeeText('Supply Dashboard')
        ->assertSeeText('Products')
        ->assertSeeText('Imports');
});
