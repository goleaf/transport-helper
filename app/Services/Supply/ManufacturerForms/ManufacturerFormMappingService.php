<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class ManufacturerFormMappingService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function saveMapping(FormTemplate $template, array $mapping, User $user): array
    {
        $validation = $this->validateMapping($template, $mapping);
        $rendererConfig = $template->renderer_config_json ?? [];
        $rendererConfig['manufacturer_mapping'] = $mapping;

        $template->update([
            'mapping_rules_json' => $mapping,
            'renderer_config_json' => $rendererConfig,
        ]);

        $template->manufacturerFormTemplateFiles()
            ->where('is_active', true)
            ->update(['mapping_json' => $mapping]);

        $this->auditLogService->write('manufacturer_form_mapping_saved', $template, $user, null, null, [
            'form_template_id' => $template->id,
            'mapping_keys' => array_keys($mapping),
        ], $template->company_id);

        return [
            'valid' => true,
            'template' => $template->fresh(),
            'validation' => $validation,
        ];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function validateMapping(FormTemplate $template, array $mapping): array
    {
        $errors = [];

        if (empty($mapping['items']['start_row'])) {
            $errors['items.start_row'] = 'Item start row is required.';
        }

        if (empty($mapping['items']['columns']) || ! is_array($mapping['items']['columns'])) {
            $errors['items.columns'] = 'Item column mapping is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function previewMapping(FormTemplate $template, SupplierOrder $order): array
    {
        return app(ManufacturerFormPreviewService::class)->preview($template, $order);
    }
}
