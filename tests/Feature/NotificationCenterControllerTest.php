<?php

use App\Services\Supply\Logistics\LogisticsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('notification index loads and mark all as read works', function () {
    $fixture = LogisticsTestSupport::fixture();
    app(LogisticsNotificationService::class)->notify('goods_expected_soon', [
        'title' => 'Goods expected soon',
        'message' => 'Delivery approaching.',
    ], ['user' => $fixture['user']]);

    $this->actingAs($fixture['user'])
        ->get(route('supply.notifications.index'))
        ->assertSuccessful()
        ->assertSee('Notifications');

    $this->actingAs($fixture['user'])
        ->post(route('supply.notifications.read-all'))
        ->assertRedirectToRoute('supply.notifications.index');

    expect($fixture['user']->unreadNotifications()->count())->toBe(0);
});
