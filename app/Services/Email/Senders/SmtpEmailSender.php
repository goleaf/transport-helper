<?php

namespace App\Services\Email\Senders;

use App\Services\Email\Concerns\ValidatesProviderConfig;

class SmtpEmailSender extends SmtpEmailSenderPlaceholder
{
    use ValidatesProviderConfig;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validateConfig(array $config): array
    {
        return $this->validateRequiredConfig($config, [
            'host',
            'port',
            'username',
            'password',
            'from_email',
        ]);
    }
}
