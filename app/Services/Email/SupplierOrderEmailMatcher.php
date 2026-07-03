<?php

namespace App\Services\Email;

use App\Enums\EmailDirection;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use Illuminate\Support\Str;

class SupplierOrderEmailMatcher
{
    /**
     * @param  array<string, mixed>  $emailData
     * @return array{supplier_order_id:?int,confidence:float,method:string,warnings:list<string>}
     */
    public function match(Company $company, array $emailData, ?int $supplierId = null): array
    {
        $text = $this->combinedText($emailData);
        $subject = Str::lower((string) ($emailData['subject'] ?? ''));

        if ($text !== '') {
            $orders = SupplierOrder::query()
                ->select(['id', 'company_id', 'supplier_id', 'order_number'])
                ->where('company_id', $company->id)
                ->when($supplierId !== null, fn ($query) => $query->where('supplier_id', $supplierId))
                ->latest('id')
                ->limit(500)
                ->get()
                ->filter(fn (SupplierOrder $order): bool => $order->order_number !== null && Str::contains($text, Str::lower($order->order_number)))
                ->values();

            if ($orders->count() === 1) {
                return [
                    'supplier_order_id' => (int) $orders->first()->id,
                    'confidence' => 0.95,
                    'method' => Str::contains($subject, Str::lower($orders->first()->order_number)) ? 'order_number_in_subject' : 'order_number_in_body',
                    'warnings' => [],
                ];
            }

            if ($orders->count() > 1) {
                return $this->noMatch(['multiple_order_matches']);
            }
        }

        $threadId = $emailData['thread_id'] ?? null;

        if (is_string($threadId) && $threadId !== '') {
            $outboundEmail = EmailMessage::query()
                ->select(['id', 'company_id', 'direction', 'thread_id', 'related_supplier_order_id'])
                ->where('company_id', $company->id)
                ->where('thread_id', $threadId)
                ->where('direction', EmailDirection::Outbound->value)
                ->whereNotNull('related_supplier_order_id')
                ->latest('id')
                ->first();

            if ($outboundEmail instanceof EmailMessage) {
                return [
                    'supplier_order_id' => (int) $outboundEmail->related_supplier_order_id,
                    'confidence' => 0.85,
                    'method' => 'thread_id_outbound_email',
                    'warnings' => [],
                ];
            }
        }

        return $this->noMatch([]);
    }

    /**
     * @param  array<string, mixed>  $emailData
     */
    private function combinedText(array $emailData): string
    {
        return Str::lower(trim(implode(' ', [
            (string) ($emailData['subject'] ?? ''),
            (string) ($emailData['body_text'] ?? ''),
        ])));
    }

    /**
     * @param  list<string>  $warnings
     * @return array{supplier_order_id:null,confidence:float,method:string,warnings:list<string>}
     */
    private function noMatch(array $warnings): array
    {
        return [
            'supplier_order_id' => null,
            'confidence' => 0.0,
            'method' => 'none',
            'warnings' => $warnings,
        ];
    }
}
