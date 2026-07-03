<?php

namespace Database\Factories;

use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormAutofillFieldValue>
 */
class FormAutofillFieldValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'form_autofill_run_id' => FormAutofillRun::factory(),
            'field_key' => fake()->bothify('field_####'),
            'extracted_value' => fake()->bothify('PO-####'),
            'normalized_value' => fake()->bothify('PO-####'),
            'final_value' => fake()->bothify('PO-####'),
            'confidence' => 92.00,
            'source_excerpt' => fake()->sentence(),
            'requires_review' => false,
            'review_reason' => null,
            'accepted_by_user_id' => User::factory(),
            'accepted_at' => now(),
        ];
    }
}
