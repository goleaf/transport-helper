<section>
    <h2>Items</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Matched by</th>
                <th>Ordered</th>
                <th>Confirmed</th>
                <th>Difference</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($confirmation->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->matched_by }}</td>
                    <td>{{ $item->ordered_quantity }}</td>
                    <td>{{ $item->confirmed_quantity }}</td>
                    <td>{{ $item->discrepancy_quantity }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No matched confirmation items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
