<?php

namespace App\Services\Supply\Transport;

use App\Enums\EmailDirection;
use App\Models\Carrier;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class CarrierQuoteRequestService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  list<int|string>  $carrierIds
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function prepareRequests(SupplierOrder $order, array $carrierIds, array $options, User $user): array
    {
        $order->loadMissing(['supplier:id,name', 'items.product:id,sku,name']);
        $carriers = Carrier::query()
            ->select(['id', 'company_id', 'name', 'code'])
            ->with(['contacts:id,carrier_id,email,name,is_active'])
            ->where('company_id', $order->company_id)
            ->whereIn('id', $carrierIds)
            ->orderBy('name')
            ->get();
        $drafts = [];
        $warnings = [];

        foreach ($carriers as $carrier) {
            $contacts = $carrier->contacts->whereNotNull('email')->values();

            if ($contacts->isEmpty()) {
                $warnings[] = 'missing_carrier_contacts_'.$carrier->id;
            }

            $subject = 'Transport quote request for order '.$order->order_number;
            $body = $this->body($order, $options);
            $emailDraft = null;

            if ($options['create_email_drafts'] ?? true) {
                $emailDraft = EmailMessage::query()->create([
                    'company_id' => $order->company_id,
                    'email_account_id' => null,
                    'direction' => EmailDirection::Outbound,
                    'message_id' => null,
                    'thread_id' => null,
                    'from_email' => 'supply@company.test',
                    'to_json' => $contacts->pluck('email')->all(),
                    'cc_json' => [],
                    'subject' => $subject,
                    'body_text' => $body,
                    'body_html' => null,
                    'received_at' => null,
                    'sent_at' => null,
                    'related_supplier_id' => $order->supplier_id,
                    'related_supplier_order_id' => $order->id,
                    'status' => 'draft',
                    'raw_headers_json' => [],
                ]);
            }

            $drafts[] = [
                'carrier' => $carrier,
                'subject' => $subject,
                'body' => $body,
                'email_draft' => $emailDraft,
                'recipients' => $contacts->pluck('email')->all(),
            ];
        }

        $this->auditLogService->write('carrier_quote_requests_prepared', $order, $user, null, null, [
            'supplier_order_id' => $order->id,
            'carrier_ids' => $carriers->pluck('id')->all(),
            'draft_count' => count($drafts),
            'create_email_drafts' => (bool) ($options['create_email_drafts'] ?? true),
            'warnings' => $warnings,
        ], $order->company_id);

        return [
            'supplier_order' => $order,
            'drafts' => $drafts,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function body(SupplierOrder $order, array $options): string
    {
        return implode("\n", array_filter([
            'Please provide a transport quote for supplier order '.$order->order_number.'.',
            'Pickup location: '.($options['pickup_location'] ?? '[pickup location]'),
            'Delivery location: '.($options['delivery_location'] ?? '[delivery location]'),
            'Ready date: '.($options['ready_date'] ?? '[ready date]'),
            'Requested pickup date: '.($options['requested_pickup_date'] ?? '[requested pickup date]'),
            'Requested delivery date: '.($options['requested_delivery_date'] ?? '[requested delivery date]'),
            'Cargo: '.($options['cargo_description'] ?? '[cargo description]'),
            'Pallet count: '.($options['pallet_count'] ?? '[pallet count]'),
            'Weight: '.($options['weight'] ?? '[weight]'),
            'Please include price, currency, pickup date, delivery date, transit days and conditions.',
        ]));
    }
}
