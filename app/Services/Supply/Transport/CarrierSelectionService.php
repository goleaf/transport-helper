<?php

namespace App\Services\Supply\Transport;

use App\Enums\CarrierQuoteStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\CarrierQuote;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarrierSelectionService
{
    public function __construct(
        private readonly TransportLogisticsUpdater $logisticsUpdater,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function select(CarrierQuote $quote, User $user, array $options = []): array
    {
        $this->validateSelection($quote, $user, $options);

        return DB::transaction(function () use ($quote, $user, $options): array {
            $quote->loadMissing('supplierOrder');
            $oldSelected = CarrierQuote::query()
                ->where('supplier_order_id', $quote->supplier_order_id)
                ->where('status', CarrierQuoteStatus::Selected)
                ->where('id', '!=', $quote->id)
                ->first();
            $oldQuoteValues = $quote->only(['status', 'selected_by_user_id', 'selected_at']);

            if ($oldSelected instanceof CarrierQuote) {
                $oldSelected->forceFill([
                    'status' => ($options['replace_status'] ?? CarrierQuoteStatus::Received->value),
                    'selected_by_user_id' => null,
                    'selected_at' => null,
                ])->save();
            }

            $quote->forceFill([
                'status' => CarrierQuoteStatus::Selected,
                'selected_by_user_id' => $user->id,
                'selected_at' => now(),
            ])->save();

            if ($options['reject_others'] ?? false) {
                CarrierQuote::query()
                    ->where('supplier_order_id', $quote->supplier_order_id)
                    ->where('id', '!=', $quote->id)
                    ->when($oldSelected instanceof CarrierQuote, fn ($query) => $query->where('id', '!=', $oldSelected->id))
                    ->whereIn('status', [CarrierQuoteStatus::Received->value, CarrierQuoteStatus::NeedsReview->value])
                    ->update([
                        'status' => CarrierQuoteStatus::Rejected,
                        'rejected_by_user_id' => $user->id,
                        'rejected_at' => now(),
                        'rejection_reason' => 'Rejected because another quote was selected.',
                    ]);
            }

            $logistics = $this->logisticsUpdater->updateAfterSelection($quote->refresh(), $options);

            $this->auditLogService->write('carrier_selected', $quote, $user, $oldQuoteValues, $quote->only(['status', 'selected_by_user_id', 'selected_at']), [
                'carrier_quote_id' => $quote->id,
                'supplier_order_id' => $quote->supplier_order_id,
                'carrier_id' => $quote->carrier_id,
                'old_selected_quote_id' => $oldSelected?->id,
                'replace_existing' => (bool) ($options['replace_existing'] ?? false),
                'override_needs_review' => (bool) ($options['override_needs_review'] ?? false),
                'override_reason' => $options['override_reason'] ?? null,
                'logistics_record_id' => $logistics['record']->id,
            ], $quote->company_id);
            $this->auditLogService->write('carrier_quote_selected', $quote, $user, $oldQuoteValues, $quote->only(['status', 'selected_by_user_id', 'selected_at']), [
                'carrier_quote_id' => $quote->id,
            ], $quote->company_id);
            $this->auditLogService->write('carrier_quote_status_changed', $quote, $user, ['status' => $oldQuoteValues['status'] ?? null], ['status' => CarrierQuoteStatus::Selected->value], [
                'carrier_quote_id' => $quote->id,
            ], $quote->company_id);
            $this->auditLogService->write('logistics_record_updated_from_carrier_selection', $logistics['record'], $user, $logistics['old_values'], $logistics['new_values'], [
                'carrier_quote_id' => $quote->id,
                'supplier_order_id' => $quote->supplier_order_id,
            ], $quote->company_id);

            if ($options['override_needs_review'] ?? false) {
                $this->auditLogService->write('transport_selection_override_used', $quote, $user, null, null, [
                    'carrier_quote_id' => $quote->id,
                    'override_reason' => $options['override_reason'] ?? null,
                ], $quote->company_id);
            }

            return [
                'quote' => $quote->refresh(),
                'supplier_order' => $quote->supplierOrder,
                'logistics_record' => $logistics['record']->refresh(),
                'warnings' => [],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function reject(CarrierQuote $quote, User $user, array $validated = []): CarrierQuote
    {
        if (! $this->canManageTransport($user)) {
            throw ValidationException::withMessages(['user' => 'User cannot reject carrier quotes.']);
        }

        return DB::transaction(function () use ($quote, $user, $validated): CarrierQuote {
            $oldValues = $quote->only(['status', 'rejected_by_user_id', 'rejected_at', 'rejection_reason']);
            $quote->forceFill([
                'status' => CarrierQuoteStatus::Rejected,
                'rejected_by_user_id' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ])->save();

            $this->auditLogService->write('carrier_quote_rejected', $quote, $user, $oldValues, $quote->only(['status', 'rejected_by_user_id', 'rejected_at', 'rejection_reason']), [
                'carrier_quote_id' => $quote->id,
                'supplier_order_id' => $quote->supplier_order_id,
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ], $quote->company_id);
            $this->auditLogService->write('carrier_quote_status_changed', $quote, $user, ['status' => $oldValues['status'] ?? null], ['status' => CarrierQuoteStatus::Rejected->value], [
                'carrier_quote_id' => $quote->id,
            ], $quote->company_id);

            return $quote->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function validateSelection(CarrierQuote $quote, User $user, array $options): void
    {
        $quote->loadMissing('supplierOrder');

        if (! $this->canSelectCarrier($user)) {
            throw ValidationException::withMessages(['user' => 'User cannot select carriers.']);
        }

        if ($quote->supplier_order_id === null || $quote->supplierOrder === null) {
            throw ValidationException::withMessages(['quote' => 'Carrier quote must belong to a supplier order.']);
        }

        if (in_array($quote->supplierOrder->status, [SupplierOrderStatus::Cancelled, SupplierOrderStatus::Completed], true)) {
            throw ValidationException::withMessages(['supplier_order' => 'Cannot select carrier for a cancelled or completed supplier order.']);
        }

        if ($quote->status === CarrierQuoteStatus::NeedsReview && ! ($options['override_needs_review'] ?? false)) {
            throw ValidationException::withMessages(['quote' => 'Needs-review quote requires override before selection.']);
        }

        if (($options['override_needs_review'] ?? false) && blank($options['override_reason'] ?? null)) {
            throw ValidationException::withMessages(['override_reason' => 'Override reason is required.']);
        }

        if (! in_array($quote->status, [CarrierQuoteStatus::Received, CarrierQuoteStatus::NeedsReview], true)) {
            throw ValidationException::withMessages(['quote' => 'Only received or needs-review quotes can be selected.']);
        }

        $existingSelected = CarrierQuote::query()
            ->where('supplier_order_id', $quote->supplier_order_id)
            ->where('status', CarrierQuoteStatus::Selected)
            ->where('id', '!=', $quote->id)
            ->exists();

        if ($existingSelected && ! ($options['replace_existing'] ?? false)) {
            throw ValidationException::withMessages(['replace_existing' => 'Replacing an existing selected carrier requires confirmation.']);
        }

        if ($quote->carrier_id === null) {
            throw ValidationException::withMessages(['carrier_id' => 'Carrier is required before selection.']);
        }

        if (! ($options['override_needs_review'] ?? false) && ($quote->price === null || $quote->delivery_date === null)) {
            throw ValidationException::withMessages(['quote' => 'Price and delivery date are required before selection.']);
        }
    }

    private function canSelectCarrier(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('select_carrier');
    }

    private function canManageTransport(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_transport');
    }
}
