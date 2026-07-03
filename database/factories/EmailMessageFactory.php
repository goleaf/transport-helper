<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email_account_id' => EmailAccount::factory(),
            'direction' => 'inbound',
            'message_id' => fake()->optional()->uuid(),
            'thread_id' => fake()->optional()->uuid(),
            'from_email' => fake()->safeEmail(),
            'to_json' => [fake()->safeEmail()],
            'cc_json' => [],
            'subject' => fake()->sentence(4),
            'body_text' => fake()->paragraph(),
            'body_html' => null,
            'received_at' => now(),
            'sent_at' => null,
            'related_supplier_id' => Supplier::factory(),
            'related_supplier_order_id' => SupplierOrder::factory(),
            'status' => 'received',
            'raw_headers_json' => [],
        ];
    }
}
