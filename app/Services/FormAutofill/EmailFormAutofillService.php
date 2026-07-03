<?php

namespace App\Services\FormAutofill;

use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Enums\FormAutofillRunStatus;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\Product;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmailFormAutofillService
{
    public function __construct(
        private readonly AiEmailFormExtractorInterface $extractor,
        private readonly AiEmailFormExtractionValidationService $validationService,
        private readonly FormFieldNormalizationService $normalizationService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createAutofillRun(EmailMessage $email, FormTemplate $template, array $options = [], ?User $user = null): array
    {
        return DB::transaction(function () use ($email, $template, $options, $user): array {
            $email->loadMissing([
                'relatedSupplier:id,name,code,default_currency',
                'relatedSupplierOrder.items.product:id,sku,name',
                'attachments:id,email_message_id,original_filename',
            ]);
            $template->loadMissing('fields');

            $context = $this->context($email);
            $input = $this->input($email, $template, $context, $options);
            $output = $this->extractor->extract($input);
            $validation = $this->validationService->validate($template, $output, $context);
            $status = $validation['status'] === 'accepted'
                ? FormAutofillRunStatus::AiFilled
                : FormAutofillRunStatus::NeedsReview;

            $run = FormAutofillRun::query()->create([
                'company_id' => $email->company_id,
                'email_message_id' => $email->id,
                'form_template_id' => $template->id,
                'ai_email_extraction_id' => null,
                'status' => $status,
                'confidence' => round($validation['overall_confidence'] * 100, 2),
                'raw_input_hash' => hash('sha256', json_encode($input)),
                'suggested_values_json' => $output,
                'validation_errors_json' => $validation['errors'],
                'warnings_json' => $validation['warnings'],
                'user_changes_json' => [],
                'created_by_user_id' => $user?->id,
            ]);

            foreach ($template->fields as $field) {
                $fieldOutput = is_array($output['fields'][$field->field_key] ?? null) ? $output['fields'][$field->field_key] : [];
                $extractedValue = $fieldOutput['value'] ?? null;
                $normalizedValue = $fieldOutput['normalized_value'] ?? $this->normalizationService->normalize($extractedValue, $field->field_type);
                $reviewReasons = $validation['field_reviews'][$field->field_key] ?? [];

                $run->fieldValues()->create([
                    'field_key' => $field->field_key,
                    'extracted_value' => $this->stringValue($extractedValue),
                    'normalized_value' => $this->stringValue($normalizedValue),
                    'final_value' => $this->stringValue($normalizedValue),
                    'confidence' => isset($fieldOutput['confidence']) ? round((float) $fieldOutput['confidence'] * 100, 2) : null,
                    'source_excerpt' => $fieldOutput['source_excerpt'] ?? null,
                    'requires_review' => $reviewReasons !== [],
                    'review_reason' => $reviewReasons === [] ? null : implode(',', $reviewReasons),
                ]);
            }

            AuditLog::query()->create([
                'company_id' => $email->company_id,
                'user_id' => $user?->id,
                'event_type' => 'form_autofill_run.created',
                'auditable_type' => $run::class,
                'auditable_id' => $run->id,
                'old_values_json' => [],
                'new_values_json' => [
                    'status' => $run->status,
                    'confidence' => $run->confidence,
                    'validation' => $validation,
                ],
                'metadata_json' => [
                    'email_message_id' => $email->id,
                    'form_template_id' => $template->id,
                ],
                'created_at' => now(),
            ]);

            return [
                'run' => $run->load(['fieldValues', 'formTemplate.fields', 'emailMessage.attachments']),
                'input' => $input,
                'ai_output' => $output,
                'validation' => $validation,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function context(EmailMessage $email): array
    {
        $supplierOrder = $email->relatedSupplierOrder;

        return [
            'supplier_order_model' => $supplierOrder,
            'supplier_order' => $supplierOrder instanceof SupplierOrder ? [
                'id' => $supplierOrder->id,
                'order_number' => $supplierOrder->order_number,
            ] : [],
            'supplier' => $email->relatedSupplier ? [
                'id' => $email->relatedSupplier->id,
                'name' => $email->relatedSupplier->name,
                'code' => $email->relatedSupplier->code,
            ] : [],
            'known_products' => $supplierOrder instanceof SupplierOrder
                ? $supplierOrder->items->map(fn ($item): array => [
                    'sku' => (string) $item->product?->sku,
                    'name' => (string) $item->product?->name,
                    'ordered_quantity' => (float) $item->ordered_quantity,
                ])->values()->all()
                : Product::query()
                    ->select(['id', 'sku', 'name'])
                    ->where('company_id', $email->company_id)
                    ->limit(100)
                    ->get()
                    ->map(fn (Product $product): array => [
                        'sku' => $product->sku,
                        'name' => $product->name,
                    ])
                    ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function input(EmailMessage $email, FormTemplate $template, array $context, array $options): array
    {
        return [
            'email' => [
                'subject' => $email->subject,
                'from' => $email->from_email,
                'body_text' => $email->body_text,
                'received_at' => $email->received_at?->toDateTimeString(),
            ],
            'template' => [
                'code' => $template->code,
                'context_type' => $template->context_type->value,
            ],
            'fields' => $template->fields->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
                'field_type' => $field->field_type->value,
                'is_required' => $field->is_required,
                'ai_extraction_hint' => $field->ai_extraction_hint,
            ])->values()->all(),
            'context' => [
                'supplier_order' => $context['supplier_order'],
                'supplier' => $context['supplier'],
                'known_products' => $context['known_products'],
            ],
            'instructions' => $options['instructions'] ?? [],
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    }
}
