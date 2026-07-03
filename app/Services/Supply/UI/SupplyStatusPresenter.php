<?php

namespace App\Services\Supply\UI;

use App\Support\DisplayValue;

class SupplyStatusPresenter
{
    /**
     * @return array{label:string,class:string,tone:string,icon:string,description:string}
     */
    public function present(mixed $status, string $context = 'default'): array
    {
        $value = DisplayValue::statusValue($status);
        $tone = $this->tone($value, $context);

        return [
            'label' => $this->label($value),
            'class' => trim('status-badge status-badge-'.$tone),
            'tone' => $tone,
            'icon' => $this->icon($tone),
            'description' => $this->description($value),
        ];
    }

    private function label(string $status): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', strtolower($status)));
    }

    private function tone(string $status, string $context): string
    {
        return match ($status) {
            'approved', 'accepted', 'active', 'completed', 'confirmed', 'converted_to_supplier_order', 'dry_run_passed', 'exported', 'passed', 'ready_for_uat', 'selected', 'sent', 'uat_passed', 'validated' => 'success',
            'cancelled', 'failed', 'rejected', 'revoked', 'send_failed' => 'danger',
            'adjusted', 'arrived', 'delayed', 'needs_review', 'partially_confirmed', 'pending_approval', 'quantity_mismatch', 'waiting_for_ready_date' => 'warning',
            'in_transit', 'order_sent', 'pickup_scheduled', 'ready_for_pickup', 'received' => $context === 'logistics' ? 'logistics' : 'info',
            'carrier_quote_needed', 'received_quote' => 'transport',
            default => 'neutral',
        };
    }

    private function icon(string $tone): string
    {
        return match ($tone) {
            'success' => 'OK',
            'warning' => '!',
            'danger' => '!',
            'logistics' => 'L',
            'transport' => 'T',
            default => '-',
        };
    }

    private function description(string $status): string
    {
        return match ($status) {
            'needs_review' => 'Human review is required.',
            'pending_approval' => 'Approval is required before this can continue.',
            'delayed' => 'A date has passed and follow-up is required.',
            'selected' => 'Selected by a user.',
            'validated' => 'Validated by Laravel and ready for the next approved action.',
            'sent' => 'Sent after approval.',
            default => DisplayValue::headline($status),
        };
    }
}
