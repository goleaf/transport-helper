<?php

namespace App\Services\Supply;

use App\Contracts\Supply\SupplierOrderTemplateRendererInterface;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailApprovalService;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService as SupplierOrderWorkflowEmailDraftService;

class SupplierOrderEmailDraftService implements SupplierOrderTemplateRendererInterface
{
    public function __construct(
        private readonly SupplierOrderWorkflowEmailDraftService $draftService,
        private readonly SupplierOrderEmailApprovalService $approvalService,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{subject:string,body_text:string,body_html:?string,to:list<string>,cc:list<string>,attachments:list<array<string,mixed>>,language:?string}
     */
    public function render(SupplierOrder $order, array $context = []): array
    {
        $order->loadMissing([
            'company:id,name',
            'supplier:id,name,default_language',
            'supplier.contacts:id,supplier_id,name,email,receives_orders,is_active',
        ]);

        $language = strtolower((string) ($context['language'] ?? $order->supplier?->default_language ?? 'en'));
        $subject = (string) ($context['subject'] ?? sprintf('Purchase order %s', $order->order_number));
        $signoff = $context['user_name'] ?? $order->company?->name ?? 'Supply team';
        $body = $language === 'lt'
            ? implode(PHP_EOL, ['Sveiki,', '', sprintf('Prisegame mūsų užsakymą %s.', $order->order_number), '', 'Pagarbiai,', $signoff])
            : implode(PHP_EOL, ['Hello,', '', sprintf('Please find attached our purchase order %s.', $order->order_number), '', 'Best regards,', $signoff]);

        return [
            'subject' => $subject,
            'body_text' => $body,
            'body_html' => null,
            'to' => $order->supplier?->contacts
                ->filter(fn ($contact): bool => $contact->is_active && $contact->receives_orders && filled($contact->email))
                ->pluck('email')
                ->values()
                ->all() ?? [],
            'cc' => [],
            'attachments' => [],
            'language' => $language === 'lt' ? 'lt' : 'en',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function prepareDraft(SupplierOrder $order, User $user, array $context = []): EmailMessage
    {
        $result = $this->draftService->prepareDraft($order, $context, $user);

        return $result['email_message'];
    }

    public function approveDraft(SupplierOrder $order, User $user): EmailMessage
    {
        $result = $this->approvalService->approveEmail($order->fresh(), [], $user);

        return $result['email_message'];
    }
}
