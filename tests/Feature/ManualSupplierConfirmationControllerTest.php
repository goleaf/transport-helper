<?php

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('manual create page loads', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-orders.confirmations.create', $fixture['supplierOrder']))
        ->assertOk()
        ->assertSee('Create Manual Confirmation');
});

it('manual store applies confirmation', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.supplier-orders.confirmations.store', $fixture['supplierOrder']), SupplierConfirmationTestSupport::manualData())
        ->assertRedirect();

    expect(SupplierConfirmation::query()->count())->toBe(1)
        ->and((float) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe(156.0);
});

it('manual store validates item identifier and quantity', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->from(route('supply.supplier-orders.confirmations.create', $fixture['supplierOrder']))
        ->post(route('supply.supplier-orders.confirmations.store', $fixture['supplierOrder']), [
            'items' => [
                ['notes' => 'Missing identifier and quantity'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['items.0.sku', 'items.0.confirmed_quantity']);
});
