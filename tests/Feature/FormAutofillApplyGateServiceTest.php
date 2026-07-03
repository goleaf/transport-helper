<?php

use App\Models\CarrierQuote;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrderItem;
use App\Services\Forms\EmailFormAutofillService;
use App\Services\Forms\FormAutofillApplyGateService;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('checks application readiness without mutating records', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $item = SupplierOrderItem::query()->firstOrFail();
    $run = app(EmailFormAutofillService::class)->createAutofillRun($fixture['email'], $fixture['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $fixture['user'])['run'];
    $blocked = app(FormAutofillApplyGateService::class)->check($run, $fixture['user']);

    app(FormAutofillReviewService::class)->validateRun($run->fresh(), $fixture['user'], ['ignore_optional_review' => true]);
    $ready = app(FormAutofillApplyGateService::class)->check($run->fresh(), $fixture['user']);

    expect($blocked['can_apply'])->toBeFalse()
        ->and($ready['can_apply'])->toBeTrue()
        ->and($ready['target_action'])->toBe('create_supplier_confirmation')
        ->and(SupplierConfirmation::query()->count())->toBe(0)
        ->and(CarrierQuote::query()->count())->toBe(0)
        ->and(LogisticsRecord::query()->count())->toBe(0)
        ->and($item->fresh()->confirmed_quantity)->toBeNull();
});
