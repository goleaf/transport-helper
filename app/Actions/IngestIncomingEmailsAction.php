<?php

namespace App\Actions;

use App\Contracts\IncomingEmailAdapter;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class IngestIncomingEmailsAction
{
    public function __construct(public ProcessManufacturerEmailAction $processManufacturerEmail) {}

    /**
     * @return array{processed: int, suggestions: int, reviews: int}
     */
    public function handle(IncomingEmailAdapter $adapter, ?User $actor = null): array
    {
        $summary = [
            'processed' => 0,
            'suggestions' => 0,
            'reviews' => 0,
        ];

        foreach ($adapter->pendingEmails() as $email) {
            $validated = Validator::make($email, [
                'from_email' => ['required', 'email'],
                'subject' => ['required', 'string'],
                'body' => ['required', 'string'],
                'received_at' => ['required', 'date'],
                'message_id' => ['nullable', 'string'],
            ])->validate();

            $manufacturerEmail = $this->processManufacturerEmail->handle(
                fromEmail: $validated['from_email'],
                subject: $validated['subject'],
                body: $validated['body'],
                receivedAt: Carbon::parse($validated['received_at']),
                actor: $actor,
                messageId: $validated['message_id'] ?? null,
            );

            $manufacturerEmail->loadCount(['aiSuggestions']);

            $summary['processed']++;
            $summary['suggestions'] += $manufacturerEmail->ai_suggestions_count;
            $summary['reviews'] += $manufacturerEmail->aiSuggestions()->withCount('humanReviews')->get()->sum('human_reviews_count');
        }

        return $summary;
    }
}
