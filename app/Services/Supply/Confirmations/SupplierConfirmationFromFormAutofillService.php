<?php

namespace App\Services\Supply\Confirmations;

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Models\FormAutofillRun;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Forms\FormAutofillApplyGateService;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationFromFormAutofillService
{
    public function __construct(
        private readonly SupplierConfirmationSourceNormalizer $sourceNormalizer,
        private readonly SupplierConfirmationApplicationService $applicationService,
        private readonly FormAutofillApplyGateService $applyGateService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(FormAutofillRun $run, User $user, array $options = []): array
    {
        $run->loadMissing('emailMessage.relatedSupplierOrder', 'formTemplate', 'fieldValues');

        if ($run->status !== FormAutofillRunStatus::Validated) {
            throw ValidationException::withMessages(['form_autofill_run' => 'Form autofill run must be validated before it can be applied.']);
        }

        $contextType = $this->contextType($run);

        if (! in_array($contextType, [
            FormTemplateContextType::SupplierConfirmation->value,
            FormTemplateContextType::ReadyDateUpdate->value,
            FormTemplateContextType::QuantityMismatch->value,
        ], true)) {
            throw ValidationException::withMessages(['form_autofill_run' => 'Form autofill context cannot be applied as supplier confirmation.']);
        }

        $gate = $this->applyGateService->check($run, $user);

        if (($gate['can_apply'] ?? false) !== true) {
            throw ValidationException::withMessages(['form_autofill_run' => 'Form autofill application gate did not pass.']);
        }

        $order = $this->resolveOrder($run, $options);
        $normalized = $this->sourceNormalizer->fromFormAutofillRun($run);

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
    private function resolveOrder(FormAutofillRun $run, array $options): SupplierOrder
    {
        if (isset($options['supplier_order_id'])) {
            return SupplierOrder::query()->findOrFail((int) $options['supplier_order_id']);
        }

        if ($run->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $run->emailMessage->relatedSupplierOrder;
        }

        $orderNumber = $run->fieldValues
            ->firstWhere('field_key', 'supplier_order_number')
            ?->final_value;

        $orderNumber = is_array($orderNumber) ? ($orderNumber['value'] ?? null) : $orderNumber;

        if (! is_string($orderNumber) || trim($orderNumber) === '') {
            throw ValidationException::withMessages(['supplier_order' => 'Supplier order could not be resolved from form autofill run.']);
        }

        $matches = SupplierOrder::query()
            ->where('company_id', $run->company_id)
            ->where('order_number', trim($orderNumber))
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages(['supplier_order' => 'Supplier order number is missing or ambiguous.']);
        }

        return $matches->first();
    }

    private function contextType(FormAutofillRun $run): string
    {
        $contextType = $run->formTemplate?->context_type;

        return $contextType instanceof \BackedEnum ? $contextType->value : (string) $contextType;
    }
}
