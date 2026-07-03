<?php

use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Models\CarrierQuote;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrderItem;
use App\Services\Forms\EmailFormAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('creates autofill runs with separated values and does not mutate business records', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $item = SupplierOrderItem::query()->firstOrFail();

    $result = app(EmailFormAutofillService::class)->createAutofillRun(
        $fixture['email'],
        $fixture['template'],
        ['extractor' => 'fake', 'fake_output' => FormAutofillTestSupport::aiOutput()],
        $fixture['user'],
    );

    $run = $result['run']->fresh('fieldValues');
    $quantity = $run->fieldValues->firstWhere('field_key', 'confirmed_quantity');

    expect($run->status)->toBe(FormAutofillRunStatus::AiFilled)
        ->and($quantity->extracted_value)->toBe('156 pcs')
        ->and($quantity->normalized_value)->toEqual(156)
        ->and($quantity->final_value)->toEqual(156)
        ->and($quantity->source_excerpt)->toBe('156 pcs confirmed')
        ->and(SupplierConfirmation::query()->count())->toBe(0)
        ->and(CarrierQuote::query()->count())->toBe(0)
        ->and(LogisticsRecord::query()->count())->toBe(0)
        ->and($item->fresh()->confirmed_quantity)->toBeNull();
});

it('returns existing run unless force new and rejects outbound emails', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $service = app(EmailFormAutofillService::class);
    $service->createAutofillRun($fixture['email'], $fixture['template'], ['extractor' => 'fake', 'fake_output' => FormAutofillTestSupport::aiOutput()], $fixture['user']);
    $existing = $service->createAutofillRun($fixture['email'], $fixture['template'], ['extractor' => 'fake', 'fake_output' => FormAutofillTestSupport::aiOutput()], $fixture['user']);
    $new = $service->createAutofillRun($fixture['email'], $fixture['template'], ['extractor' => 'fake', 'force_new' => true, 'fake_output' => FormAutofillTestSupport::aiOutput()], $fixture['user']);

    $fixture['email']->forceFill(['direction' => EmailDirection::Outbound])->save();

    expect($existing['warnings'])->toContain('existing_run_returned')
        ->and($new['run']->id)->not->toBe($existing['run']->id)
        ->and(fn () => $service->createAutofillRun($fixture['email'], $fixture['template'], ['extractor' => 'fake'], $fixture['user']))
        ->toThrow(ValidationException::class);
});
