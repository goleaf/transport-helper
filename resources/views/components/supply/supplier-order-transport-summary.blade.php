<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Carrier decision</p>
            <h2>Transport</h2>
        </div>
        <div class="actions">
            <x-supply.button :href="route('supply.transport.orders.quotes', $order)" size="sm" mode="outline" variant="neutral">Compare carrier quotes</x-supply.button>
            @if ($selectedLogisticsRecord)
                <x-supply.button :href="route('supply.logistics.show', $selectedLogisticsRecord)" size="sm" mode="outline" variant="neutral">Open logistics record</x-supply.button>
            @endif
            @if ($canManageTransport)
                <x-supply.button :href="route('supply.transport.orders.quote-requests.create', $order)" size="sm" mode="outline" variant="neutral">Prepare quote requests</x-supply.button>
                <x-supply.button :href="route('supply.transport.orders.quotes.create', $order)" size="sm" mode="outline" variant="neutral">Add manual quote</x-supply.button>
            @endif
        </div>
    </div>

    <dl>
        <dt>Quote count</dt>
        <dd>{{ $quoteCount }}</dd>
        <dt>Selected carrier</dt>
        <dd>{{ $selectedQuote?->carrier?->name ?? $selectedLogisticsRecord?->carrier?->name ?? 'Not selected' }}</dd>
        <dt>Selected quote price</dt>
        <dd>{{ $selectedQuote?->price ?? 'Not set' }} {{ $selectedQuote?->currency }}</dd>
        <dt>Pickup date</dt>
        <dd>{{ $selectedLogisticsRecord?->pickup_date?->toDateString() ?? $selectedQuote?->pickup_date?->toDateString() ?? 'Not set' }}</dd>
        <dt>Delivery date</dt>
        <dd>{{ $selectedLogisticsRecord?->delivery_date?->toDateString() ?? $selectedQuote?->delivery_date?->toDateString() ?? 'Not set' }}</dd>
    </dl>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Carrier</th>
                <th>Status</th>
                <th>Price</th>
                <th>Pickup</th>
                <th>Delivery</th>
                <th>Transit</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->carrierQuotes as $quote)
                <tr>
                    <td>{{ $quote->carrier?->name }}</td>
                    <td><x-supply.status-badge :status="$quote->status" /></td>
                    <td>{{ $quote->price ?? 'Not set' }} {{ $quote->currency }}</td>
                    <td>{{ $quote->pickup_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $quote->delivery_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $quote->transit_days ?? 'Not set' }}</td>
                    <td>{{ $quote->calculated_score ?? 'Not scored' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No carrier quotes yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
