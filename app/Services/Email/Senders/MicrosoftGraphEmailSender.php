<?php

namespace App\Services\Email\Senders;

use App\Services\Email\Concerns\ValidatesProviderConfig;

class MicrosoftGraphEmailSender extends MicrosoftGraphEmailSenderPlaceholder
{
    use ValidatesProviderConfig;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validateConfig(array $config): array
    {
        return $this->validateRequiredConfig($config, [
            'tenant_id',
            'client_id',
            'client_secret',
            'mailbox',
        ]);
    }
}
