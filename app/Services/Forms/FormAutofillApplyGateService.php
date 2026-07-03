<?php

namespace App\Services\Forms;

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\UserRole;
use App\Models\FormAutofillRun;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class FormAutofillApplyGateService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function check(FormAutofillRun $run, User $user): array
    {
        $run->loadMissing(['formTemplate.fields', 'fieldValues']);
        $blockingReasons = [];

        if ($run->status !== FormAutofillRunStatus::Validated) {
            $blockingReasons[] = 'run_not_validated';
        }

        if (! $this->canApply($user)) {
            $blockingReasons[] = 'user_not_authorized';
        }

        $fieldsByKey = $run->fieldValues->keyBy('field_key');

        foreach ($run->formTemplate->fields as $templateField) {
            $field = $fieldsByKey->get($templateField->field_key);

            if ($templateField->is_required && ($field === null || $field->final_value === null || $field->final_value === '')) {
                $blockingReasons[] = 'required_field_missing_'.$templateField->field_key;
            }

            if ($templateField->is_required && $field?->requires_review) {
                $blockingReasons[] = 'required_field_requires_review_'.$templateField->field_key;
            }
        }

        $contextType = $run->formTemplate->context_type instanceof \BackedEnum
            ? $run->formTemplate->context_type->value
            : (string) $run->formTemplate->context_type;
        $targetAction = $this->targetAction($contextType);
        $canApply = $blockingReasons === [];
        $result = [
            'can_apply' => $canApply,
            'context_type' => $contextType,
            'target_action' => $targetAction,
            'message' => $canApply
                ? 'Ready for target-specific application in next workflow stage.'
                : 'Autofill run is not ready for target-specific application.',
            'final_values' => $run->fieldValues->mapWithKeys(fn ($field): array => [$field->field_key => $field->final_value])->all(),
            'warnings' => [],
            'blocking_reasons' => array_values(array_unique($blockingReasons)),
        ];

        $this->auditLogService->write('form_autofill_apply_gate_checked', $run, $user, null, null, [
            'run_id' => $run->id,
            'can_apply' => $canApply,
            'context_type' => $contextType,
            'target_action' => $targetAction,
            'blocking_reasons' => $result['blocking_reasons'],
        ], $run->company_id);

        return $result;
    }

    private function canApply(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin])
            || $user->hasPermissionTo('apply_email_form_autofill');
    }

    private function targetAction(string $contextType): string
    {
        return match ($contextType) {
            FormTemplateContextType::SupplierConfirmation->value => 'create_supplier_confirmation',
            FormTemplateContextType::ReadyDateUpdate->value => 'update_ready_date',
            FormTemplateContextType::QuantityMismatch->value => 'create_quantity_discrepancy_review',
            FormTemplateContextType::CarrierQuote->value => 'create_carrier_quote',
            FormTemplateContextType::LogisticsUpdate->value => 'update_logistics_record',
            FormTemplateContextType::SupplierOrder->value => 'prepare_supplier_order_form',
            default => 'store_custom_form_output',
        };
    }
}
