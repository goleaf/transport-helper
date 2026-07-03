<?php

namespace Database\Factories;

use App\Models\AiEmailExtraction;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarrierQuote>
 */
class CarrierQuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_order_id' => SupplierOrder::factory(),
            'carrier_id' => Carrier::factory(),
            'email_message_id' => EmailMessage::factory(),
            'price' => fake()->randomFloat(3, 100, 2500),
            'currency' => 'EUR',
            'pickup_date' => now()->addDays(12)->toDateString(),
            'delivery_date' => now()->addDays(17)->toDateString(),
            'transit_days' => 5,
            'conditions' => fake()->optional()->sentence(),
            'reliability_score' => 90.00,
            'calculated_score' => 85.000,
            'score_explanation_json' => [
                'price_weight' => 0.5,
                'time_weight' => 0.3,
                'reliability_weight' => 0.2,
            ],
            'status' => 'received',
            'created_from_ai_extraction_id' => AiEmailExtraction::factory(),
            'created_from_form_autofill_run_id' => null,
            'source_type' => 'manual',
            'source_id' => null,
            'created_by_user_id' => null,
            'selected_by_user_id' => null,
            'selected_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'validation_errors_json' => null,
            'warnings_json' => null,
        ];
    }
}
