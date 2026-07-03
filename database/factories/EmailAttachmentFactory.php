<?php

namespace Database\Factories;

use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAttachment>
 */
class EmailAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email_message_id' => EmailMessage::factory(),
            'original_filename' => fake()->lexify('attachment-????.pdf'),
            'stored_path' => fake()->lexify('attachments/????.pdf'),
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 500000),
            'checksum' => fake()->sha256(),
        ];
    }
}
