<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('health page displays checks', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.health.index'))
        ->assertSuccessful()
        ->assertSee('database');
});
