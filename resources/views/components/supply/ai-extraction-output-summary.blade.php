<dl>
    <dt>Email type</dt>
    <dd>{{ $output['email_type'] ?? 'unclear' }}</dd>

    <dt>Supplier order number</dt>
    <dd>{{ $output['supplier_order_number'] ?? '' }}</dd>

    <dt>Supplier reference</dt>
    <dd>{{ $output['supplier_reference'] ?? '' }}</dd>

    <dt>Confirmed items</dt>
    <dd>{{ $confirmedItemsCount }}</dd>

    <dt>Questions to supplier</dt>
    <dd>{{ $questionsToSupplierCount }}</dd>
</dl>
