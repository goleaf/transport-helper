<section>
    <h2>Transport</h2>
    <dl>
        <dt>Quote count</dt>
        <dd>{{ $quoteCount }}</dd>
        <dt>Selected carrier</dt>
        <dd>{{ $selectedQuote?->carrier?->name ?? $selectedLogisticsRecord?->carrier?->name ?? 'Not selected' }}</dd>
        <dt>Selected quote price</dt>
        <dd>{{ $selectedQuote?->price }} {{ $selectedQuote?->currency }}</dd>
        <dt>Pickup date</dt>
        <dd>{{ $selectedLogisticsRecord?->pickup_date?->toDateString() ?? $selectedQuote?->pickup_date?->toDateString() }}</dd>
        <dt>Delivery date</dt>
        <dd>{{ $selectedLogisticsRecord?->delivery_date?->toDateString() ?? $selectedQuote?->delivery_date?->toDateString() }}</dd>
    </dl>
    <p><a href="{{ route('supply.transport.orders.quotes', $order) }}">Compare carrier quotes</a></p>
    @if ($canManageTransport)
        <p><a href="{{ route('supply.transport.orders.quote-requests.create', $order) }}">Prepare quote requests</a></p>
        <p><a href="{{ route('supply.transport.orders.quotes.create', $order) }}">Add manual quote</a></p>
    @endif
</section>
