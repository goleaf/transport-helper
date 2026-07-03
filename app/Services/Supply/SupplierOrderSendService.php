<?php

namespace App\Services\Supply;

use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\SupplierOrders\SupplierOrderSendService as SupplierOrderWorkflowSendService;

class SupplierOrderSendService
{
    public function __construct(
        private readonly SupplierOrderWorkflowSendService $sendService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(SupplierOrder $order, User $user, array $options = []): EmailMessage
    {
        if (array_key_exists('no_attachment_confirmed', $options) && ! array_key_exists('confirm_no_attachment', $options)) {
            $order->forceFill([
                'no_attachment_confirmed' => (bool) $options['no_attachment_confirmed'],
            ])->save();
        }

        $result = $this->sendService->send($order->fresh(), $options, $user);

        return $result['email_message'];
    }
}
