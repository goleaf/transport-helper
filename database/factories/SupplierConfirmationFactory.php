<?php

namespace Database\Factories;

use App\Models\AiEmailExtraction;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierConfirmation>
 */
class SupplierConfirmationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_order_id' => SupplierOrder::factory(),
            'email_message_id' => EmailMessage::factory(),
            'supplier_reference' => fake()->bothify('CONF-####'),
            'confirmation_date' => now()->toDateString(),
            'ready_date' => now()->addDays(10)->toDateString(),
            'shipping_date' => now()->addDays(12)->toDateString(),
            'expected_arrival_date' => now()->addDays(18)->toDateString(),
            'status' => 'needs_review',
            'discrepancy_summary' => null,
            'created_from_ai_extraction_id' => AiEmailExtraction::factory(),
            'created_from_form_autofill_run_id' => null,
            'source_type' => 'manual',
            'source_id' => null,
            'output_json' => null,
            'discrepancies_json' => null,
            'applied_by_user_id' => null,
            'applied_at' => null,
        ];
    }
}
