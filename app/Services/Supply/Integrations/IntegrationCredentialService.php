<?php

namespace App\Services\Supply\Integrations;

use Illuminate\Support\Facades\Crypt;

class IntegrationCredentialService
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function encryptConfig(array $config): string
    {
        return Crypt::encryptString(json_encode($config, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptConfig(string|array|null $encrypted): array
    {
        if (is_array($encrypted)) {
            return $encrypted;
        }

        if ($encrypted === null || $encrypted === '') {
            return [];
        }

        $decoded = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function maskConfig(array $config): array
    {
        $masked = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskConfig($value);

                continue;
            }

            $masked[$key] = $this->isSensitiveKey((string) $key)
                ? $this->maskValue((string) $key, $value)
                : $value;
        }

        return $masked;
    }

    public function containsSecretValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsSecretValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return is_string($value) && preg_match('/(secret|token|password|api[_-]?key|client[_-]?secret|refresh[_-]?token)/i', $value) === 1;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/(token|password|secret|client_secret|private_key|api_key|refresh_token|access_token)/i', $key) === 1;
    }

    private function maskValue(string $key, mixed $value): string
    {
        if ($key === 'api_key' && is_scalar($value) && strlen((string) $value) > 4) {
            return '********'.substr((string) $value, -4);
        }

        return '********';
    }
}
