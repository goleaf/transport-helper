<?php

namespace App\Services\Supply\Confirmations;

use App\Models\AiEmailExtraction;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationFromAiExtractionService
{
    public function __construct(
        private readonly SupplierConfirmationSourceNormalizer $sourceNormalizer,
        private readonly SupplierConfirmationApplicationService $applicationService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(AiEmailExtraction $extraction, User $user, array $options = []): array
    {
        $extraction->loadMissing('emailMessage.relatedSupplierOrder');

        if ($extraction->accepted_at === null) {
            throw ValidationException::withMessages(['ai_email_extraction' => 'AI extraction must be accepted before it can be applied.']);
        }

        if ($extraction->rejected_at !== null) {
            throw ValidationException::withMessages(['ai_email_extraction' => 'Rejected AI extraction cannot be applied.']);
        }

        $order = $this->resolveOrder($extraction, $options);
        $normalized = $this->sourceNormalizer->fromAiExtraction($extraction);

        return $this->applicationService->apply($order, $normalized, $user, [
            'update_inbound' => (bool) ($options['update_inbound'] ?? true),
            'update_logistics' => (bool) ($options['update_logistics'] ?? true),
            'allow_over_confirmation' => (bool) ($options['allow_over_confirmation'] ?? false),
            'allow_missing_items' => (bool) ($options['allow_missing_items'] ?? true),
            'reapply_allowed' => (bool) ($options['reapply_allowed'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveOrder(AiEmailExtraction $extraction, array $options): SupplierOrder
    {
        if (isset($options['supplier_order_id'])) {
            return SupplierOrder::query()->findOrFail((int) $options['supplier_order_id']);
        }

        if ($extraction->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $extraction->emailMessage->relatedSupplierOrder;
        }

        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $orderNumber = $output['supplier_order_number'] ?? null;

        if (! is_string($orderNumber) || trim($orderNumber) === '') {
            throw ValidationException::withMessages(['supplier_order' => 'Supplier order could not be resolved from AI extraction.']);
        }

        $matches = SupplierOrder::query()
            ->where('company_id', $extraction->emailMessage?->company_id)
            ->where('order_number', trim($orderNumber))
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages(['supplier_order' => 'Supplier order number is missing or ambiguous.']);
        }

        return $matches->first();
    }
}
