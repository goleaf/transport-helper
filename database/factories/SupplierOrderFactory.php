<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierOrder>
 */
class SupplierOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'order_proposal_id' => OrderProposal::factory(),
            'order_number' => fake()->unique()->bothify('PO-####'),
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'sent_by_user_id' => User::factory(),
            'sent_at' => null,
            'email_message_id' => null,
            'email_subject' => null,
            'email_body' => null,
            'email_approved_at' => null,
            'email_approved_by_user_id' => null,
            'no_attachment_confirmed' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
