<?php

namespace App\Services\Forms;

use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\AI\Forms\ExternalAiEmailFormExtractorPlaceholder;
use App\Services\AI\Forms\FakeAiEmailFormExtractor;
use App\Services\AI\Forms\RuleBasedAiEmailFormExtractor;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailFormAutofillService
{
    public function __construct(
        private readonly FormAutofillContextBuilder $contextBuilder,
        private readonly AiEmailFormExtractionValidationService $validationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createAutofillRun(EmailMessage $email, FormTemplate $template, array $options = [], ?User $user = null): array
    {
        return DB::transaction(function () use ($email, $template, $options, $user): array {
            if ($email->direction !== EmailDirection::Inbound) {
                throw ValidationException::withMessages([
                    'email' => 'Only inbound emails can be used for form autofill.',
                ]);
            }

            $template->loadMissing('fields');

            if (! $template->is_active) {
                throw ValidationException::withMessages([
                    'form_template_id' => 'Selected form template is not active.',
                ]);
            }

            if ($template->fields->isEmpty()) {
                throw ValidationException::withMessages([
                    'form_template_id' => 'Selected form template has no fields.',
                ]);
            }

            if (($options['force_new'] ?? false) !== true) {
                $existing = FormAutofillRun::query()
                    ->where('email_message_id', $email->id)
                    ->where('form_template_id', $template->id)
                    ->whereNotIn('status', [FormAutofillRunStatus::Rejected->value, FormAutofillRunStatus::Failed->value])
                    ->with(['fieldValues', 'formTemplate.fields', 'emailMessage.attachments'])
                    ->latest('id')
                    ->first();

                if ($existing instanceof FormAutofillRun) {
                    return [
                        'run' => $existing,
                        'input' => null,
                        'ai_output' => $existing->suggested_values_json,
                        'validation' => [
                            'status' => $existing->status->value,
                            'warnings' => ['existing_run_returned'],
                        ],
                        'warnings' => ['existing_run_returned'],
                    ];
                }
            }

            $context = $this->contextBuilder->build($email, $template);
            $input = $this->input($email, $template, $context, $options);
            $rawInputHash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
            $extractorName = (string) ($options['extractor'] ?? config('supply.form_autofill.default_extractor', 'rule_based'));

            try {
                $aiOutput = $this->resolveExtractor($extractorName)->extract($input);
                $validation = $this->validationService->validate($template, $aiOutput, $context, (array) ($options['thresholds'] ?? []));
                $status = match ($validation['status']) {
                    'valid' => FormAutofillRunStatus::AiFilled,
                    'invalid' => FormAutofillRunStatus::Failed,
                    default => FormAutofillRunStatus::NeedsReview,
                };

                $run = FormAutofillRun::query()->create([
                    'company_id' => $email->company_id,
                    'email_message_id' => $email->id,
                    'form_template_id' => $template->id,
                    'ai_email_extraction_id' => $options['ai_email_extraction_id'] ?? null,
                    'status' => $status,
                    'confidence' => $validation['confidence'],
                    'raw_input_hash' => $rawInputHash,
                    'suggested_values_json' => $aiOutput,
                    'validation_errors_json' => $validation['errors'],
                    'warnings_json' => $validation['warnings'],
                    'user_changes_json' => [],
                    'created_by_user_id' => $user?->id,
                ]);

                foreach ($template->fields as $field) {
                    $result = $validation['field_results'][$field->field_key] ?? [
                        'extracted_value' => null,
                        'normalized_value' => null,
                        'confidence' => 0.0,
                        'source_excerpt' => null,
                        'requires_review' => true,
                        'review_reason' => 'field_not_returned',
                    ];

                    $requiresReview = (bool) ($result['requires_review'] ?? true);
                    $hasErrors = ($result['errors'] ?? []) !== [];
                    $normalizedValue = $result['normalized_value'] ?? null;

                    $run->fieldValues()->create([
                        'field_key' => $field->field_key,
                        'extracted_value' => $result['extracted_value'] ?? null,
                        'normalized_value' => $normalizedValue,
                        'final_value' => (! $requiresReview && ! $hasErrors) ? $normalizedValue : null,
                        'confidence' => $result['confidence'] ?? null,
                        'source_excerpt' => $result['source_excerpt'] ?? null,
                        'requires_review' => $requiresReview,
                        'review_reason' => $result['review_reason'] ?? null,
                    ]);
                }

                $run->load(['fieldValues', 'formTemplate.fields', 'emailMessage.attachments']);
                $reviewFieldsCount = $run->fieldValues->where('requires_review', true)->count();

                $this->auditLogService->write('form_autofill_created', $run, $user, null, [
                    'status' => $run->status->value,
                    'confidence' => $run->confidence,
                ], [
                    'run_id' => $run->id,
                    'email_message_id' => $email->id,
                    'form_template_id' => $template->id,
                    'context_type' => $template->context_type instanceof \BackedEnum ? $template->context_type->value : $template->context_type,
                    'extractor' => $extractorName,
                    'confidence' => $run->confidence,
                    'status' => $run->status->value,
                    'fields_count' => $run->fieldValues->count(),
                    'review_fields_count' => $reviewFieldsCount,
                    'warnings' => $validation['warnings'],
                    'errors' => $validation['errors'],
                ], $run->company_id);

                return [
                    'run' => $run,
                    'input' => $input,
                    'ai_output' => $aiOutput,
                    'validation' => $validation,
                    'warnings' => $validation['warnings'],
                ];
            } catch (Throwable $exception) {
                $this->auditLogService->write('form_autofill_failed', null, $user, null, null, [
                    'email_message_id' => $email->id,
                    'form_template_id' => $template->id,
                    'extractor' => $extractorName,
                    'error' => $exception->getMessage(),
                ], $email->company_id);

                throw $exception;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function input(EmailMessage $email, FormTemplate $template, array $context, array $options): array
    {
        $template->loadMissing('fields');

        return [
            'email' => [
                'id' => $email->id,
                'subject' => $email->subject,
                'from_email' => $email->from_email,
                'body_text' => $email->body_text,
                'received_at' => $email->received_at?->toDateTimeString(),
                'attachments_summary' => ($options['include_attachments_summary'] ?? true) ? $context['attachments_summary'] : [],
            ],
            'template' => [
                'id' => $template->id,
                'code' => $template->code,
                'name' => $template->name,
                'context_type' => $template->context_type instanceof \BackedEnum ? $template->context_type->value : $template->context_type,
                'format_type' => $template->format_type instanceof \BackedEnum ? $template->format_type->value : $template->format_type,
                'version' => $template->version,
            ],
            'fields' => $template->fields->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
                'field_type' => $field->field_type instanceof \BackedEnum ? $field->field_type->value : $field->field_type,
                'is_required' => $field->is_required,
                'validation_rules' => $field->validation_rules_json ?? [],
                'ai_extraction_hint' => $field->ai_extraction_hint,
            ])->values()->all(),
            'context' => [
                'supplier' => $context['supplier'],
                'supplier_order' => $context['supplier_order'],
                'expected_items' => $context['expected_items'],
                'known_products' => $context['known_products'],
                'known_carriers' => $context['known_carriers'],
            ],
            'instructions' => [
                'return_only_defined_fields' => true,
                'include_source_excerpt' => true,
                'do_not_invent_values' => true,
                'mark_ambiguous_dates' => true,
                'human_review_by_default' => true,
                'fake_output' => $options['fake_output'] ?? null,
            ],
            'fake_output' => $options['fake_output'] ?? null,
        ];
    }

    private function resolveExtractor(string $extractor): AiEmailFormExtractorInterface
    {
        return match ($extractor) {
            'fake' => app(FakeAiEmailFormExtractor::class),
            'external' => app(ExternalAiEmailFormExtractorPlaceholder::class),
            'rule_based' => app(AiEmailFormExtractorInterface::class),
            default => app(RuleBasedAiEmailFormExtractor::class),
        };
    }
}
