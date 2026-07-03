<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Services\Email\SupplierOrderEmailMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('matches order number in subject', function () {
    [$company, $supplier, $order] = stage6OrderMatchFixture('PO-20260701-1');

    $match = app(SupplierOrderEmailMatcher::class)->match($company, [
        'subject' => 'Confirmation PO-20260701-1',
        'body_text' => '',
    ], $supplier->id);

    expect($match['supplier_order_id'])->toBe($order->id)
        ->and($match['method'])->toBe('order_number_in_subject');
});

it('matches order number in body', function () {
    [$company, $supplier, $order] = stage6OrderMatchFixture('PO-20260701-2');

    $match = app(SupplierOrderEmailMatcher::class)->match($company, [
        'subject' => 'Confirmation',
        'body_text' => 'We confirm PO-20260701-2.',
    ], $supplier->id);

    expect($match['supplier_order_id'])->toBe($order->id)
        ->and($match['method'])->toBe('order_number_in_body');
});

it('ambiguous order match returns warning', function () {
    [$company, $supplier] = stage6OrderMatchFixture('PO-20260701-3');
    SupplierOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-20260701-33',
        'status' => SupplierOrderStatus::Sent,
    ]);

    $match = app(SupplierOrderEmailMatcher::class)->match($company, [
        'subject' => 'PO-20260701-3 and PO-20260701-33',
        'body_text' => '',
    ], $supplier->id);

    expect($match['supplier_order_id'])->toBeNull()
        ->and($match['warnings'])->toContain('multiple_order_matches');
});

it('matches by thread id to previous outbound email', function () {
    [$company, $supplier, $order] = stage6OrderMatchFixture('PO-THREAD-1');
    EmailMessage::factory()->create([
        'company_id' => $company->id,
        'direction' => EmailDirection::Outbound,
        'thread_id' => 'thread-match',
        'related_supplier_id' => $supplier->id,
        'related_supplier_order_id' => $order->id,
        'status' => 'sent',
    ]);

    $match = app(SupplierOrderEmailMatcher::class)->match($company, [
        'subject' => 'Re: confirmation',
        'body_text' => '',
        'thread_id' => 'thread-match',
    ], $supplier->id);

    expect($match['supplier_order_id'])->toBe($order->id)
        ->and($match['method'])->toBe('thread_id_outbound_email');
});

it('no match returns null', function () {
    $company = Company::factory()->create();

    $match = app(SupplierOrderEmailMatcher::class)->match($company, [
        'subject' => 'No order here',
        'body_text' => '',
    ]);

    expect($match['supplier_order_id'])->toBeNull();
});

function stage6OrderMatchFixture(string $orderNumber): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'order_number' => $orderNumber,
        'status' => SupplierOrderStatus::Sent,
    ]);

    return [$company, $supplier, $order];
}
