<table>
    <thead>
        <tr>
            <th>Rank</th>
            <th>Carrier</th>
            <th>Price</th>
            <th>Pickup date</th>
            <th>Delivery date</th>
            <th>Transit days</th>
            <th>Reliability</th>
            <th>Score</th>
            <th>Warnings</th>
            <th>Status</th>
            <th>Selected?</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($quotes as $quote)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $quote->carrier?->name }}</td>
                <td>{{ $quote->price }} {{ $quote->currency }}</td>
                <td>{{ $quote->pickup_date?->toDateString() }}</td>
                <td>{{ $quote->delivery_date?->toDateString() }}</td>
                <td>{{ $quote->transit_days }}</td>
                <td>{{ $quote->reliability_score }}</td>
                <td>{{ $quote->calculated_score }}</td>
                <td>{{ $quote->warnings_text }}</td>
                <td>@include('supply.transport.partials.quote-status-badge', ['status' => $quote->status])</td>
                <td>{{ $quote->is_selected ? 'Yes' : 'No' }}</td>
                <td>@include('supply.transport.partials.quote-actions', ['quote' => $quote])</td>
            </tr>
        @empty
            <tr>
                <td colspan="12">No carrier quotes.</td>
            </tr>
        @endforelse
    </tbody>
</table>
