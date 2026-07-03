<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Order lines</p>
            <h2>Items</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Manufacturer SKU</th>
                <th>Product</th>
                <th>Ordered</th>
                <th>Confirmed</th>
                <th>Received</th>
                <th>Unit price</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->manufacturer_sku ?? 'Not set' }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ number_format((float) $item->ordered_quantity, 3) }} {{ $item->product?->unit }}</td>
                    <td>{{ number_format((float) ($item->confirmed_quantity ?? 0), 3) }} {{ $item->product?->unit }}</td>
                    <td>{{ number_format((float) ($item->received_quantity ?? 0), 3) }} {{ $item->product?->unit }}</td>
                    <td>{{ $item->unit_price ?? 'Not set' }} {{ $item->currency }}</td>
                    <td><x-supply.status-badge :status="$item->status" /></td>
                    <td>{{ $item->notes ?? 'No notes' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
