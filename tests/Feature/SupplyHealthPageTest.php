<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('admin can view health page', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.health.index'))
        ->assertSuccessful()
        ->assertSee('Supply Health');
});

it('viewer cannot view health page', function () {
    LogisticsTestSupport::fixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.health.index'))
        ->assertForbidden();
});
