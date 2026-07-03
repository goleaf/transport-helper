<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplateField>
 */
class FormTemplateFieldFactory extends Factory
{
    public function definition(): array
    {
        $fieldKey = fake()->bothify('field_####');

        return [
            'form_template_id' => FormTemplate::factory(),
            'field_key' => $fieldKey,
            'label' => str($fieldKey)->replace('_', ' ')->title()->toString(),
            'field_type' => fake()->randomElement(['text', 'number', 'date', 'email']),
            'is_required' => fake()->boolean(70),
            'validation_rules_json' => ['required'],
            'ai_extraction_hint' => 'Extract this value only when it appears explicitly in the email.',
            'default_value_json' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
