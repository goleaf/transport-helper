<?php

namespace App\Services\Email\Providers;

use App\Services\Email\Concerns\ValidatesProviderConfig;

class ImapEmailProvider extends ImapEmailProviderPlaceholder
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
        ]);
    }
}
