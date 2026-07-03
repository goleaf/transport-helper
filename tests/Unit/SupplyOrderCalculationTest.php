<?php

use App\Actions\CalculateSupplyOrderQuantitiesAction;

it('calculates t0 t1 t2 t3 with the required 150 to 156 reserve example', function () {
    $quantities = app(CalculateSupplyOrderQuantitiesAction::class)->handle(
        requestedQuantity: 150,
        availableQuantity: 0,
        incomingQuantity: 0,
        reservedQuantity: 0,
    );

    expect($quantities)->toMatchArray([
        't0_requested_quantity' => 150,
        't1_available_quantity' => 0,
        't2_required_quantity' => 150,
        't3_manufacturer_quantity' => 156,
        'reserve_percent' => 4,
    ]);
});

it('subtracts usable leftovers before applying the manufacturer reserve', function () {
    $quantities = app(CalculateSupplyOrderQuantitiesAction::class)->handle(
        requestedQuantity: 200,
        availableQuantity: 40,
        incomingQuantity: 15,
        reservedQuantity: 5,
    );

    expect($quantities)->toMatchArray([
        't0_requested_quantity' => 200,
        't1_available_quantity' => 50,
        't2_required_quantity' => 150,
        't3_manufacturer_quantity' => 156,
        'reserve_percent' => 4,
    ]);
});

it('rejects negative quantities', function () {
    app(CalculateSupplyOrderQuantitiesAction::class)->handle(
        requestedQuantity: 150,
        availableQuantity: -1,
        incomingQuantity: 0,
        reservedQuantity: 0,
    );
})->throws(InvalidArgumentException::class);

it('does not create a manufacturer quantity when stock fully covers demand', function () {
    $quantities = app(CalculateSupplyOrderQuantitiesAction::class)->handle(
        requestedQuantity: 150,
        availableQuantity: 200,
        incomingQuantity: 0,
        reservedQuantity: 0,
    );

    expect($quantities)->toMatchArray([
        't0_requested_quantity' => 150,
        't1_available_quantity' => 200,
        't2_required_quantity' => 0,
        't3_manufacturer_quantity' => 0,
        'reserve_percent' => 4,
    ]);
});
