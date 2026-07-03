<?php

namespace App\Services\Email\Concerns;

trait ValidatesProviderConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    protected function validateRequiredConfig(array $config, array $required): array
    {
        $missing = [];

        foreach ($required as $key) {
            if (! array_key_exists($key, $config) || $config[$key] === null || $config[$key] === '') {
                $missing[] = $key;
            }
        }

        return [
            'valid' => $missing === [],
            'missing' => $missing,
            'real_call_performed' => false,
        ];
    }
}
