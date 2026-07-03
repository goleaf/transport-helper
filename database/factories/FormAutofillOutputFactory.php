<?php

namespace Database\Factories;

use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormAutofillOutput>
 */
class FormAutofillOutputFactory extends Factory
{
    public function definition(): array
    {
        return [
            'form_autofill_run_id' => FormAutofillRun::factory(),
            'output_type' => fake()->randomElement(['json', 'csv', 'html']),
            'filename' => fake()->optional()->lexify('form-output-????.json'),
            'stored_path' => fake()->optional()->lexify('form-autofill/????.json'),
            'content_json' => [
                'order_number' => fake()->bothify('PO-####'),
                'quantity' => 156,
            ],
            'status' => 'ready',
            'created_by_user_id' => User::factory(),
        ];
    }
}
