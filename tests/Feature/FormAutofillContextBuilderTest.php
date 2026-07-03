<?php

use App\Models\EmailAttachment;
use App\Services\Forms\FormAutofillContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('builds context from related email supplier order and attachments', function () {
    $fixture = FormAutofillTestSupport::fixture();
    EmailAttachment::factory()->create([
        'email_message_id' => $fixture['email']->id,
        'original_filename' => 'confirmation.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 123,
    ]);

    $context = app(FormAutofillContextBuilder::class)->build($fixture['email'], $fixture['template']);

    expect($context['supplier']['name'])->toBe('Acme Manufacturing')
        ->and($context['supplier_order']['order_number'])->toBe('PO-AUTOFILL-1')
        ->and($context['expected_items'])->toHaveCount(1)
        ->and($context['attachments_summary'][0]['filename'])->toBe('confirmation.pdf');
});
