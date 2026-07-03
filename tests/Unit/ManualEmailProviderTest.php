<?php

use App\Services\Email\Providers\ManualEmailProvider;

it('returns messages from options', function () {
    $messages = app(ManualEmailProvider::class)->fetchMessages(null, [
        'messages' => [
            [
                'message_id' => 'manual-1',
                'from_email' => 'orders@supplier.test',
                'to' => ['supply@company.test'],
                'subject' => 'Confirmation PO-1',
                'body_text' => 'Confirmed.',
                'received_at' => '2026-07-02 10:00:00',
            ],
        ],
    ]);

    expect($messages)->toHaveCount(1)
        ->and($messages[0]['message_id'])->toBe('manual-1')
        ->and($messages[0]['from_email'])->toBe('orders@supplier.test')
        ->and($messages[0]['to'])->toBe(['supply@company.test']);
});

it('generates message id when missing', function () {
    $messages = app(ManualEmailProvider::class)->fetchMessages(null, [
        'messages' => [
            [
                'from_email' => 'orders@supplier.test',
                'subject' => 'No id',
                'body_text' => 'Body',
            ],
        ],
    ]);

    expect($messages[0]['message_id'])->toStartWith('manual-');
});

it('defaults received at when missing', function () {
    $messages = app(ManualEmailProvider::class)->fetchMessages(null, [
        'messages' => [
            [
                'message_id' => 'manual-default-date',
                'from_email' => 'orders@supplier.test',
            ],
        ],
    ]);

    expect($messages[0]['received_at'])->not->toBeNull();
});

it('preserves attachments', function () {
    $messages = app(ManualEmailProvider::class)->fetchMessages(null, [
        'messages' => [
            [
                'message_id' => 'manual-attachment',
                'from_email' => 'orders@supplier.test',
                'attachments' => [
                    [
                        'original_filename' => 'confirmation.txt',
                        'content' => 'attached',
                    ],
                ],
            ],
        ],
    ]);

    expect($messages[0]['attachments'])->toHaveCount(1)
        ->and($messages[0]['attachments'][0]['content'])->toBe('attached');
});
