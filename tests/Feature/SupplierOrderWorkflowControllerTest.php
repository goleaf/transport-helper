<?php

use App\Enums\SupplierOrderStatus;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailApprovalService;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

it('loads supplier order index', function () {
    $fixture = stage5SupplierOrderFixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-orders.index'))
        ->assertSuccessful()
        ->assertSee('Supplier Orders')
        ->assertSee('Acme Manufacturing');
});

it('loads supplier order show with export and email panels', function () {
    $fixture = stage5SupplierOrderFixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-orders.show', $fixture['order']))
        ->assertSuccessful()
        ->assertSee('Export spreadsheet')
        ->assertSee('Email workflow')
        ->assertSee('Axle Bearing 150');
});

it('export route creates export file', function () {
    $fixture = stage5SupplierOrderFixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.supplier-orders.export', $fixture['order']), ['format' => 'csv'])
        ->assertRedirect(route('supply.supplier-orders.show', $fixture['order']));

    expect(ExportFile::query()->count())->toBe(1);
});

it('prepare email route creates draft', function () {
    $fixture = stage5SupplierOrderFixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.supplier-orders.prepare-email', $fixture['order']))
        ->assertRedirect(route('supply.supplier-orders.show', $fixture['order']));

    expect(EmailMessage::query()->where('status', 'draft')->count())->toBe(1);
});

it('approve email route approves draft', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    $this->actingAs($fixture['user'])
        ->post(route('supply.supplier-orders.approve-email', $fixture['order']->fresh()))
        ->assertRedirect(route('supply.supplier-orders.show', $fixture['order']));

    expect($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::Approved);
});

it('send email route sends with log sender', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);
    app(SupplierOrderEmailApprovalService::class)->approveEmail($fixture['order']->fresh(), [], $fixture['user']);

    $this->actingAs($fixture['user'])
        ->post(route('supply.supplier-orders.send-email', $fixture['order']->fresh()), ['sender' => 'log'])
        ->assertRedirect(route('supply.supplier-orders.show', $fixture['order']));

    expect($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::Sent);
});

it('viewer cannot send email', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);
    app(SupplierOrderEmailApprovalService::class)->approveEmail($fixture['order']->fresh(), [], $fixture['user']);

    $this->actingAs($fixture['viewer'])
        ->post(route('supply.supplier-orders.send-email', $fixture['order']->fresh()), ['sender' => 'log'])
        ->assertForbidden();
});

it('user without approval permission cannot approve email', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    $this->actingAs($fixture['viewer'])
        ->post(route('supply.supplier-orders.approve-email', $fixture['order']->fresh()))
        ->assertForbidden();
});

it('pdf export route returns configured error', function () {
    $fixture = stage5SupplierOrderFixture();

    $this->actingAs($fixture['user'])
        ->from(route('supply.supplier-orders.show', $fixture['order']))
        ->post(route('supply.supplier-orders.export', $fixture['order']), ['format' => 'pdf'])
        ->assertRedirect(route('supply.supplier-orders.show', $fixture['order']))
        ->assertSessionHasErrors('format');
});

it('show page displays prepared email and export file', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-orders.show', $fixture['order']->fresh()))
        ->assertSuccessful()
        ->assertSee('Purchase order PO-TEST-1')
        ->assertSee('PO-TEST-1_excel.csv');
});
