<?php

namespace App\Services\AI\Email;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Enums\AiPromptVersion;
use App\Enums\EmailDirection;
use App\Exceptions\NotConfiguredYetException;
use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiEmailAnalysisService
{
    public function __construct(
        private readonly AiEmailExtractionValidationService $validationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{extraction:AiEmailExtraction,validation:array<string,mixed>,email:EmailMessage}
     */
    public function analyze(EmailMessage $email, array $options = [], ?User $user = null): array
    {
        $email->refresh();

        if ($email->direction !== EmailDirection::Inbound) {
            throw ValidationException::withMessages([
                'email' => 'Only inbound email messages can be analyzed.',
            ]);
        }

        if (($options['force'] ?? false) !== true) {
            $existing = $email->aiEmailExtractions()->latest('id')->first();

            if ($existing instanceof AiEmailExtraction) {
                return [
                    'extraction' => $existing,
                    'validation' => $this->validationService->validate(is_array($existing->output_json) ? $existing->output_json : [], $this->buildContext($email)),
                    'email' => $email,
                ];
            }
        }

        $context = $this->buildContext($email);
        $input = [
            'email' => [
                'id' => $email->id,
                'subject' => $email->subject,
                'from_email' => $email->from_email,
                'body_text' => $email->body_text,
                'received_at' => $email->received_at?->toDateTimeString(),
            ],
            'context' => $context,
            'instructions' => [
                'do_not_invent_values' => true,
                'return_json_only' => true,
                'mark_uncertainty' => true,
            ],
            'fake_output' => $options['fake_output'] ?? null,
        ];
        $analyzerName = (string) ($options['analyzer'] ?? config('supply.email_ingestion.default_analyzer', 'rule_based'));
        $output = $this->resolveAnalyzer($analyzerName, $options)->analyze($input);
        $validation = $this->validationService->validate($output, $context);
        $normalized = $validation['normalized_output'];

        return DB::transaction(function () use ($email, $user, $options, $input, $analyzerName, $output, $validation, $normalized): array {
            $extraction = AiEmailExtraction::query()->create([
                'email_message_id' => $email->id,
                'provider' => $analyzerName,
                'model' => $options['model'] ?? $this->modelNameFor($analyzerName),
                'prompt_version' => $options['prompt_version'] ?? AiPromptVersion::SupplierEmailParserV1,
                'input_hash' => hash('sha256', (string) json_encode($input)),
                'output_json' => $normalized + [
                    '_validation' => [
                        'status' => $validation['status'],
                        'warnings' => $validation['warnings'],
                        'errors' => $validation['errors'],
                        'discrepancies' => $validation['discrepancies'],
                    ],
                    '_raw_output' => $output,
                ],
                'confidence' => $normalized['confidence'],
                'requires_human_review' => $validation['requires_human_review'],
                'review_reason' => $validation['review_reason'],
            ]);

            $email->forceFill([
                'status' => $this->emailStatusFor($normalized, $validation),
            ])->save();

            $eventType = $validation['valid_shape'] ? 'ai_extraction_created' : 'ai_extraction_validation_failed';
            $this->auditLogService->write($eventType, $extraction, $user, null, null, [
                'ai_email_extraction_id' => $extraction->id,
                'email_message_id' => $email->id,
                'provider' => $analyzerName,
                'prompt_version' => $extraction->prompt_version?->value ?? $extraction->prompt_version,
                'email_type' => $normalized['email_type'] ?? null,
                'confidence' => $normalized['confidence'] ?? null,
                'requires_human_review' => $validation['requires_human_review'],
                'review_reason' => $validation['review_reason'],
                'warnings' => $validation['warnings'],
                'errors' => $validation['errors'],
            ], $email->company_id);

            return [
                'extraction' => $extraction,
                'validation' => $validation,
                'email' => $email,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(EmailMessage $email): array
    {
        $email->loadMissing([
            'relatedSupplier:id,name,code,default_language',
            'relatedSupplierOrder:id,company_id,supplier_id,order_number,status',
            'relatedSupplierOrder.items.product.supplierProductRules',
        ]);

        $order = $email->relatedSupplierOrder;

        $orderSupplierId = $order?->supplier_id;

        return [
            'supplier' => $email->relatedSupplier ? [
                'id' => $email->relatedSupplier->id,
                'name' => $email->relatedSupplier->name,
                'code' => $email->relatedSupplier->code,
                'default_language' => $email->relatedSupplier->default_language,
            ] : null,
            'supplier_order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status?->value ?? $order->status,
            ] : null,
            'expected_items' => $order?->items?->map(function ($item) use ($orderSupplierId): array {
                $rule = $item->product?->supplierProductRules?->firstWhere('supplier_id', $orderSupplierId)
                    ?? $item->product?->supplierProductRules?->first();

                return [
                    'product_id' => $item->product_id,
                    'sku' => $item->product?->sku,
                    'manufacturer_sku' => $item->product?->manufacturer_sku,
                    'supplier_sku' => $rule?->supplier_sku,
                    'ordered_quantity' => (float) $item->ordered_quantity,
                ];
            })->values()->all() ?? [],
            'known_products' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveAnalyzer(string $analyzerName, array $options): AiEmailAnalyzerInterface
    {
        return match ($analyzerName) {
            'fake' => new FakeAiEmailAnalyzer(is_array($options['fake_output'] ?? null) ? $options['fake_output'] : null),
            'rule_based' => app(RuleBasedAiEmailAnalyzer::class),
            'external', 'external_placeholder' => app(ExternalAiEmailAnalyzerPlaceholder::class),
            default => throw NotConfiguredYetException::forAdapter('ai_email_analyzer_'.$analyzerName),
        };
    }

    private function modelNameFor(string $analyzerName): string
    {
        return match ($analyzerName) {
            'fake' => FakeAiEmailAnalyzer::class,
            'rule_based' => RuleBasedAiEmailAnalyzer::class,
            default => ExternalAiEmailAnalyzerPlaceholder::class,
        };
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $validation
     */
    private function emailStatusFor(array $normalized, array $validation): string
    {
        if (($normalized['email_type'] ?? null) === 'unclear') {
            return 'unclear';
        }

        return $validation['requires_human_review'] ? 'needs_review' : 'analyzed';
    }
}
