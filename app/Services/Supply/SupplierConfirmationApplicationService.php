<?php

namespace App\Services\Supply;

use App\Models\AiEmailExtraction;
use App\Models\FormAutofillRun;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\Confirmations\SupplierConfirmationApplicationService as ConfirmationApplicationService;
use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationApplicationService
{
    public function __construct(
        private readonly ConfirmationApplicationService $applicationService,
        private readonly SupplierConfirmationSourceNormalizer $sourceNormalizer,
    ) {}

    /**
     * Compatibility entrypoint kept for older task tests and callers.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function apply(array $input): array
    {
        $order = SupplierOrder::query()->findOrFail((int) ($input['supplier_order_id'] ?? 0));
        $user = User::query()->find((int) ($input['applied_by_user_id'] ?? 0));

        if (! $user instanceof User) {
            throw ValidationException::withMessages(['applied_by_user_id' => 'An applying user is required.']);
        }

        $normalized = $this->normalizedSource($input);

        return $this->applicationService->apply($order, $normalized, $user, [
            'update_inbound' => (bool) ($input['update_inbound'] ?? true),
            'update_logistics' => (bool) ($input['update_logistics'] ?? true),
            'allow_over_confirmation' => (bool) ($input['allow_over_confirmation'] ?? false),
            'allow_missing_items' => (bool) ($input['allow_missing_items'] ?? true),
            'reapply_allowed' => (bool) ($input['reapply_allowed'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizedSource(array $input): array
    {
        if (! empty($input['form_autofill_run_id'])) {
            $run = FormAutofillRun::query()->with('fieldValues')->findOrFail((int) $input['form_autofill_run_id']);

            return $this->sourceNormalizer->fromFormAutofillRun($run);
        }

        if (! empty($input['ai_email_extraction_id'])) {
            $extraction = AiEmailExtraction::query()->findOrFail((int) $input['ai_email_extraction_id']);

            return $this->sourceNormalizer->fromAiExtraction($extraction);
        }

        $manualData = is_array($input['manual_confirmation_data'] ?? null) ? $input['manual_confirmation_data'] : [];

        return $this->sourceNormalizer->fromManual($manualData);
    }
}
