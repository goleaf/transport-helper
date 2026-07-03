<?php

namespace Database\Factories;

use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use App\Models\AiSuggestion;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSuggestion>
 */
class AiSuggestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supply_order_id' => SupplyOrder::factory(),
            'manufacturer_email_id' => null,
            'created_by_id' => User::factory(),
            'reviewed_by_id' => null,
            'applied_by_id' => null,
            'type' => AiSuggestionType::EmailConfirmation,
            'status' => AiSuggestionStatus::PendingReview,
            'confidence_score' => 80,
            'requires_review' => true,
            'source_adapter' => 'factory',
            'payload' => [
                'confirmation_number' => fake()->bothify('CONF-###'),
            ],
            'conflicts' => [],
            'notes' => null,
            'reviewed_at' => null,
            'applied_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiSuggestionStatus::Approved,
            'reviewed_by_id' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
