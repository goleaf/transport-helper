<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\SupplierConfirmationApplicationService;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationFromAiExtractionService
{
    public function __construct(
        private readonly SupplierConfirmationApplicationService $applicationService,
    ) {}

    public function create(AiEmailExtraction $extraction, ?User $user = null): SupplierConfirmation
    {
        $extraction->loadMissing('emailMessage.relatedSupplierOrder');
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $supplierOrder = $this->supplierOrderFor($extraction, $output);

        if (! $supplierOrder instanceof SupplierOrder) {
            throw ValidationException::withMessages([
                'supplier_order' => 'Supplier confirmation requires a linked supplier order.',
            ]);
        }

        $result = $this->applicationService->apply([
            'supplier_order_id' => $supplierOrder->id,
            'ai_email_extraction_id' => $extraction->id,
            'form_autofill_run_id' => null,
            'manual_confirmation_data' => [],
            'applied_by_user_id' => $user?->id,
        ]);

        return $result['confirmation'];
    }

    private function supplierOrderFor(AiEmailExtraction $extraction, array $output): ?SupplierOrder
    {
        if ($extraction->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $extraction->emailMessage->relatedSupplierOrder;
        }

        $orderNumber = $output['supplier_order_number'] ?? null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return SupplierOrder::query()
            ->where('company_id', $extraction->emailMessage?->company_id)
            ->where('order_number', $orderNumber)
            ->first();
    }
}
