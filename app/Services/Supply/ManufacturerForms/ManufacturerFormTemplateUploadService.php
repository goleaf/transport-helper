<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Models\FormTemplate;
use App\Models\ManufacturerFormTemplateFile;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ManufacturerFormTemplateUploadService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function upload(FormTemplate $template, UploadedFile $file, array $options, User $user): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'pdf'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Manufacturer form template must be xlsx, xls, csv or pdf.',
            ]);
        }

        $basePath = 'manufacturer-form-templates/'.($options['supplier_id'] ?? $template->supplier_id ?? $template->company_id);
        $filename = now()->format('YmdHis').'-'.str()->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$extension;
        $storedPath = $file->storeAs($basePath, $filename);
        $contents = Storage::get($storedPath);

        $templateFile = ManufacturerFormTemplateFile::query()->create([
            'form_template_id' => $template->id,
            'supplier_id' => $options['supplier_id'] ?? $template->supplier_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash('sha256', $contents),
            'version' => $options['version'] ?? '1',
            'mapping_json' => $options['mapping'] ?? null,
            'validation_rules_json' => $options['validation_rules'] ?? null,
            'is_active' => true,
            'uploaded_by_user_id' => $user->id,
        ]);

        $rendererConfig = $template->renderer_config_json ?? [];
        $rendererConfig['manufacturer_form_template_file_id'] = $templateFile->id;
        $rendererConfig['manufacturer_form_private_path'] = $storedPath;
        $template->update(['renderer_config_json' => $rendererConfig]);

        $this->auditLogService->write('manufacturer_form_template_uploaded', $templateFile, $user, null, null, [
            'form_template_id' => $template->id,
            'supplier_id' => $templateFile->supplier_id,
            'original_filename' => $templateFile->original_filename,
            'stored_path' => $templateFile->stored_path,
            'checksum' => $templateFile->checksum,
        ], $template->company_id);

        return [
            'file' => $templateFile,
            'template_file' => $templateFile,
            'stored_path' => $storedPath,
        ];
    }
}
