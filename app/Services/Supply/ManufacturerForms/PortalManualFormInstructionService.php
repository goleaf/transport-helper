<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Models\FormTemplate;
use App\Models\SupplierOrder;

class PortalManualFormInstructionService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function instructions(FormTemplate|SupplierOrder $first, SupplierOrder|FormTemplate $second, array $options = []): array
    {
        $template = $first instanceof FormTemplate ? $first : $second;
        $order = $first instanceof SupplierOrder ? $first : $second;
        $preview = app(ManufacturerFormPreviewService::class)->preview($template, $order, $options);
        $portalUrl = $options['portal_url']
            ?? data_get($template->renderer_config_json, 'portal_url');

        return [
            'portal_url' => $portalUrl,
            'header' => $preview['header'],
            'items' => $preview['items'],
            'warnings' => $preview['warnings'],
            'checklist' => [
                'Log in to the supplier portal manually.',
                'Copy header fields from the preview.',
                'Copy each item row and quantity.',
                'Review supplier portal validation before submitting.',
                'Do not store portal credentials in this output.',
            ],
        ];
    }
}
