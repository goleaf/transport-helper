<x-mail::message>
# Supply Order {{ $order->order_number }}

Please prepare the manufacturer order below.

| Field | Value |
| --- | --- |
| SKU | {{ $order->product->sku }} |
| Product | {{ $order->product->name }} |
| Quantity | {{ $order->manufacturer_quantity }} {{ $order->product->unit }} |
| Customer reference | {{ $order->customer_reference ?? 'n/a' }} |

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
