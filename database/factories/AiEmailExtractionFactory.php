<?php

namespace Database\Factories;

use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiEmailExtraction>
 */
class AiEmailExtractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email_message_id' => EmailMessage::factory(),
            'provider' => 'fake',
            'model' => 'test-model',
            'prompt_version' => 'supplier_email_parser_v1',
            'input_hash' => fake()->sha256(),
            'output_json' => [
                'supplier_reference' => fake()->bothify('CONF-####'),
                'expected_arrival_date' => now()->addDays(21)->toDateString(),
            ],
            'confidence' => 90.00,
            'requires_human_review' => true,
            'review_reason' => 'pending_human_approval',
            'reviewed_by_user_id' => User::factory(),
            'reviewed_at' => null,
            'accepted_at' => null,
            'rejected_at' => null,
        ];
    }
}
