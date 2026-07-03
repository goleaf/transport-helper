<?php

use App\Enums\FormAutofillRunStatus;
use App\Services\Forms\EmailFormAutofillService;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('accepts edits rejects and validates fields', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $run = app(EmailFormAutofillService::class)->createAutofillRun(
        $fixture['email'],
        $fixture['template'],
        ['extractor' => 'fake', 'fake_output' => FormAutofillTestSupport::aiOutput(['fields' => ['confirmed_quantity' => ['confidence' => 0.5]]])],
        $fixture['user'],
    )['run']->fresh('fieldValues');
    $service = app(FormAutofillReviewService::class);
    $field = $run->fieldValues->firstWhere('field_key', 'confirmed_quantity');

    $service->acceptField($field, $fixture['user']);
    $service->updateField($field->fresh(), ['final_value' => '155', 'reason' => 'Supplier correction'], $fixture['user']);
    $service->rejectField($field->fresh(), $fixture['user'], ['reason' => 'Need proof']);
    $service->updateField($field->fresh(), ['final_value' => '156'], $fixture['user']);
    $validation = $service->validateRun($run->fresh(), $fixture['user'], ['ignore_optional_review' => true, 'mismatch_reviewed' => true]);

    expect($field->fresh()->final_value)->toEqual(156)
        ->and($validation['status'])->toBe(FormAutofillRunStatus::Validated->value)
        ->and(fn () => $service->updateField($field->fresh(), ['final_value' => 'bad-date'], $fixture['user']))
        ->toThrow(ValidationException::class);
});
