<?php

namespace Database\Factories;

use App\Models\AiEmailExtraction;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormAutofillRun>
 */
class FormAutofillRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email_message_id' => EmailMessage::factory(),
            'form_template_id' => FormTemplate::factory(),
            'ai_email_extraction_id' => AiEmailExtraction::factory(),
            'status' => 'needs_review',
            'confidence' => 88.50,
            'raw_input_hash' => fake()->sha256(),
            'suggested_values_json' => [
                'order_number' => fake()->bothify('PO-####'),
                'quantity' => 156,
            ],
            'validation_errors_json' => [],
            'warnings_json' => [],
            'user_changes_json' => null,
            'created_by_user_id' => User::factory(),
            'reviewed_by_user_id' => null,
            'applied_by_user_id' => null,
            'applied_at' => null,
        ];
    }
}
