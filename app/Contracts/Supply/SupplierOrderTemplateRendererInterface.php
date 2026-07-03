<?php

namespace App\Contracts\Supply;

use App\Models\SupplierOrder;

interface SupplierOrderTemplateRendererInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{subject:string,body_text:string,body_html:?string,to:list<string>,cc:list<string>,attachments:list<array<string,mixed>>,language:?string}
     */
    public function render(SupplierOrder $order, array $context = []): array;
}
