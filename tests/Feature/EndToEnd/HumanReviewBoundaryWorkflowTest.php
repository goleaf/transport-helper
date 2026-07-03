<?php

use App\Enums\FormAutofillRunStatus;
use App\Services\Forms\EmailFormAutofillService;
use App\Services\Supply\Calculation\OrderNeedCalculator;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrders\SupplierOrderSendService;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\FormAutofillTestSupport;
use Tests\Support\TransportTestSupport;

require_once dirname(__DIR__).'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

it('calculation missing last year sales requires review', function (): void {
    $result = app(OrderNeedCalculator::class)->calculate([
        'company_id' => 1,
        'supplier_id' => 1,
        'product_id' => 1,
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-14',
        't3_date' => '2026-09-01',
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 0,
        'last_year_sales_t0_t1' => 40,
        'last_year_sales_t1_t2' => 100,
        'last_year_sales_t2_t3' => 60,
        'free_stock' => 70,
        'inbound_until_t1' => 0,
        'inbound_t1_t3' => 20,
        'reserved_quantity' => 0,
        'reservation_strategy' => 'reserved_not_removed_from_free_stock',
    ]);

    expect($result['status'])->toBe('needs_review')
        ->and($result['requires_human_review'])->toBeTrue();
});

it('form autofill low confidence required field blocks validation state', function (): void {
    $fixture = FormAutofillTestSupport::fixture();
    $run = app(EmailFormAutofillService::class)->createAutofillRun(
        $fixture['email'],
        $fixture['template'],
        [
            'extractor' => 'fake',
            'fake_output' => FormAutofillTestSupport::aiOutput([
                'fields' => [
                    'supplier_order_number' => ['value' => '', 'confidence' => 0.30, 'source_excerpt' => 'unclear order'],
                ],
            ]),
        ],
        $fixture['user'],
    );

    expect($run['run']->status)->not->toBe(FormAutofillRunStatus::Validated);
});

it('supplier email cannot be sent without approval', function (): void {
    Storage::fake(config('filesystems.default'));
    $fixture = stage5SupplierOrderFixture();

    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    app(SupplierOrderSendService::class)->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);
})->throws(ValidationException::class);

it('carrier cannot be selected when needs review without override', function (): void {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture, ['status' => 'needs_review']);

    app(CarrierSelectionService::class)->select($quote, $fixture['user'], ['confirmation' => true]);
})->throws(ValidationException::class);
