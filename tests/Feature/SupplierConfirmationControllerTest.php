<?php

use App\Services\Supply\Confirmations\SupplierConfirmationApplicationService;
use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('index loads', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-confirmations.index'))
        ->assertOk()
        ->assertSee('Supplier Confirmations');
});

it('show loads and displays discrepancies', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $result = app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData([
            'items' => [['sku' => 'UNKNOWN', 'confirmed_quantity' => 1]],
        ])),
        $fixture['user'],
    );

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-confirmations.show', $result['confirmation']))
        ->assertOk()
        ->assertSee('unknown_sku');
});
