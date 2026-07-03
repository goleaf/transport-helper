<dl>
    <dt>Order date</dt>
    <dd>{{ $record->order_date?->toDateString() }}</dd>
    <dt>Confirmation date</dt>
    <dd>{{ $record->confirmation_date?->toDateString() }}</dd>
    <dt>Ready date</dt>
    <dd>{{ $record->ready_date?->toDateString() }}</dd>
    <dt>Pickup date</dt>
    <dd>{{ $record->pickup_date?->toDateString() }}</dd>
    <dt>Delivery date</dt>
    <dd>{{ $record->delivery_date?->toDateString() }}</dd>
    <dt>Actual received date</dt>
    <dd>{{ $record->actual_received_date?->toDateString() }}</dd>
</dl>
