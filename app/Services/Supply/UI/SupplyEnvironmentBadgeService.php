<?php

namespace App\Services\Supply\UI;

class SupplyEnvironmentBadgeService
{
    /**
     * @return list<array{label:string,tone:string,description:string}>
     */
    public function badges(): array
    {
        $environment = app()->environment();

        return [
            [
                'label' => $environment === 'production' ? 'PRODUCTION' : 'LOCAL MODE',
                'tone' => $environment === 'production' ? 'neutral' : 'success',
                'description' => 'Current application environment: '.$environment.'.',
            ],
            [
                'label' => config('supply.external_ai.enabled', false) ? 'EXTERNAL AI ON' : 'EXTERNAL AI OFF',
                'tone' => config('supply.external_ai.enabled', false) ? 'danger' : 'success',
                'description' => config('supply.external_ai.enabled', false)
                    ? 'External AI is enabled and must be governed.'
                    : 'External AI is disabled.',
            ],
            [
                'label' => config('supply.integrations.real_calls_enabled', false) ? 'REAL INTEGRATIONS ON' : 'REAL INTEGRATIONS OFF',
                'tone' => config('supply.integrations.real_calls_enabled', false) ? 'danger' : 'success',
                'description' => config('supply.integrations.real_calls_enabled', false)
                    ? 'Real integration calls are enabled.'
                    : 'Real integration calls are disabled.',
            ],
            [
                'label' => config('supply.pilot.allow_real_email_send', false) ? 'REAL EMAIL ON' : 'REAL EMAIL OFF',
                'tone' => config('supply.pilot.allow_real_email_send', false) ? 'danger' : 'success',
                'description' => config('supply.pilot.allow_real_email_send', false)
                    ? 'Pilot real email sending is enabled.'
                    : 'Pilot real email sending is disabled.',
            ],
        ];
    }
}
