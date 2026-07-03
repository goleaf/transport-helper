@props(['output'])

<dl>
    <dt>Email type</dt>
    <dd>{{ $output['email_type'] ?? 'unclear' }}</dd>

    <dt>Supplier order number</dt>
    <dd>{{ $output['supplier_order_number'] ?? '' }}</dd>

    <dt>Supplier reference</dt>
    <dd>{{ $output['supplier_reference'] ?? '' }}</dd>

    <dt>Confirmed items</dt>
    <dd>{{ count($output['confirmed_items'] ?? []) }}</dd>

    <dt>Questions to supplier</dt>
    <dd>{{ count($output['questions_to_supplier'] ?? []) }}</dd>
</dl>
