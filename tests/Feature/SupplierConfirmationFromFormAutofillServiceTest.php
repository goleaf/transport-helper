<?php

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Models\AuditLog;
use App\Services\Supply\Confirmations\SupplierConfirmationFromFormAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('applies from validated form autofill run', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    $result = app(SupplierConfirmationFromFormAutofillService::class)->apply($run, $fixture['user']);

    expect($result['confirmation']->created_from_form_autofill_run_id)->toBe($run->getKey())
        ->and($run->fresh()->status)->toBe(FormAutofillRunStatus::Applied);
});

it('rejects unvalidated run', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);
    $run->forceFill(['status' => FormAutofillRunStatus::NeedsReview])->save();

    app(SupplierConfirmationFromFormAutofillService::class)->apply($run, $fixture['user']);
})->throws(ValidationException::class);

it('rejects incompatible context', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture, FormTemplateContextType::CarrierQuote->value);

    app(SupplierConfirmationFromFormAutofillService::class)->apply($run, $fixture['user']);
})->throws(ValidationException::class);

it('application gate must pass', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);
    $run->fieldValues()->where('field_key', 'confirmed_quantity')->update(['final_value' => null]);

    app(SupplierConfirmationFromFormAutofillService::class)->apply($run->fresh(), $fixture['user']);
})->throws(ValidationException::class);

it('form autofill application writes audit', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    app(SupplierConfirmationFromFormAutofillService::class)->apply($run, $fixture['user']);

    expect(AuditLog::query()->where('event_type', 'form_autofill_run_applied')->exists())->toBeTrue();
});
