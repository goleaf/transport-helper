<?php

use App\Services\Supply\Logistics\LogisticsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('creates and deduplicates database notifications by unique key', function () {
    $fixture = LogisticsTestSupport::fixture();

    $service = app(LogisticsNotificationService::class);
    $service->notify('goods_expected_soon', [
        'title' => 'Goods expected soon',
        'message' => 'Delivery is approaching.',
        'unique_key' => 'goods-expected-'.$fixture['logisticsRecord']->id,
    ], ['user' => $fixture['user']]);
    $service->notify('goods_expected_soon', [
        'title' => 'Goods expected soon',
        'message' => 'Delivery is approaching.',
        'unique_key' => 'goods-expected-'.$fixture['logisticsRecord']->id,
    ], ['user' => $fixture['user']]);

    expect($fixture['user']->notifications()->count())->toBe(1);
});

it('notification center can mark notifications as read', function () {
    $fixture = LogisticsTestSupport::fixture();
    app(LogisticsNotificationService::class)->notify('receiving_mismatch', [
        'title' => 'Mismatch',
        'message' => 'Mismatch detected.',
    ], ['user' => $fixture['user']]);
    $notification = $fixture['user']->notifications()->first();

    $this->actingAs($fixture['user'])
        ->post(route('supply.notifications.read', $notification->id))
        ->assertRedirect();

    expect($fixture['user']->notifications()->first()->read_at)->not->toBeNull();
});
