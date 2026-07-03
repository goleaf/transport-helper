@props(['items'])

<table class="table table-zebra">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Base recommended</th>
            <th>Simulated recommended</th>
            <th>Difference</th>
            <th>Trend used</th>
            <th>Seasonality factor</th>
            <th>Warnings</th>
            <th>Review</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{{ $item->product?->sku }}</td>
                <td>
                    <strong>{{ $item->product?->name }}</strong>
                    <span>{{ $item->product?->category ?? 'No category' }}</span>
                </td>
                <td>{{ $item->base_recommended_quantity ?? 'Not available' }}</td>
                <td>{{ $item->simulated_recommended_quantity ?? 'Not available' }}</td>
                <td>{{ $item->difference_quantity ?? 'Not available' }}</td>
                <td>{{ $item->trend_used ?? 'Not available' }}</td>
                <td>{{ $item->seasonality_factor ?? '1.000000' }}</td>
                <td>
                    @forelse ($item->warnings_json ?? [] as $warning)
                        <span>{{ $warning }}</span>
                    @empty
                        <span>No warnings</span>
                    @endforelse
                </td>
                <td>{{ $item->requires_human_review ? 'Required' : 'Not required' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No scenario items.</td>
            </tr>
        @endforelse
    </tbody>
</table>
