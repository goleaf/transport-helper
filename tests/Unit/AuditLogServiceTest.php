<?php

use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('logs created models with attributes', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-AUDIT-1']);
    $user = User::factory()->create();

    $log = app(AuditLogService::class)->logCreated($product, $user, ['source' => 'test']);

    expect(AuditLog::query()->count())->toBe(1)
        ->and($log->event_type)->toBe('product_created')
        ->and($log->auditable_type)->toBe(Product::class)
        ->and($log->auditable_id)->toBe($product->id)
        ->and($log->company_id)->toBe($company->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->old_values_json)->toBeNull()
        ->and($log->new_values_json['sku'])->toBe('SKU-AUDIT-1')
        ->and($log->metadata_json['source'])->toBe('test');
});

it('stores old and new values for updates', function () {
    $product = Product::factory()->create(['sku' => 'SKU-OLD']);

    $log = app(AuditLogService::class)->logUpdated(
        $product,
        ['sku' => 'SKU-OLD'],
        ['sku' => 'SKU-NEW'],
    );

    expect($log->event_type)->toBe('product_updated')
        ->and($log->old_values_json)->toMatchArray(['sku' => 'SKU-OLD'])
        ->and($log->new_values_json)->toMatchArray(['sku' => 'SKU-NEW']);
});

it('logs status changes', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create(['status' => 'draft']);

    $log = app(AuditLogService::class)->logStatusChanged($proposal, 'draft', 'approved');

    expect($log->event_type)->toBe('order_proposal_status_changed')
        ->and($log->old_values_json)->toMatchArray(['status' => 'draft'])
        ->and($log->new_values_json)->toMatchArray(['status' => 'approved']);
});

it('logs decisions with metadata', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create();
    $item = OrderProposalItem::factory()->for($proposal, 'orderProposal')->create();
    $user = User::factory()->create();

    $log = app(AuditLogService::class)->logDecision('order_quantity_adjusted', $item, $user, [
        'reason' => 'manual correction',
    ]);

    expect($log->event_type)->toBe('order_quantity_adjusted')
        ->and($log->metadata_json['reason'])->toBe('manual correction')
        ->and($log->user_id)->toBe($user->id);
});

it('works without an HTTP request context', function () {
    $product = Product::factory()->create();

    $log = app(AuditLogService::class)->logCreated($product);

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->event_type)->toBe('product_created');
});

it('resolves company id for nested order proposal items', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create();
    $item = OrderProposalItem::factory()->for($proposal, 'orderProposal')->create();

    $log = app(AuditLogService::class)->logDecision('order_quantity_approved', $item);

    expect($log->company_id)->toBe($company->id);
});
