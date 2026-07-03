<?php

namespace Database\Factories;

use App\Enums\HumanReviewStatus;
use App\Models\AiSuggestion;
use App\Models\HumanReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HumanReview>
 */
class HumanReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_suggestion_id' => AiSuggestion::factory(),
            'assigned_to_id' => null,
            'reviewed_by_id' => null,
            'status' => HumanReviewStatus::Pending,
            'reason' => 'ai_output_requires_human_approval',
            'priority' => 'normal',
            'context' => [],
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewed_by_id' => User::factory(),
            'status' => HumanReviewStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
