<?php

namespace App\Services\Email\Senders;

use App\Services\Email\Concerns\ValidatesProviderConfig;

class GmailEmailSender extends GmailEmailSenderPlaceholder
{
    use ValidatesProviderConfig;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validateConfig(array $config): array
    {
        return $this->validateRequiredConfig($config, [
            'client_id',
            'client_secret',
            'refresh_token',
        ]);
    }
}
