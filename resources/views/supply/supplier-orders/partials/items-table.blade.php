<section>
    <h2>Items</h2>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Manufacturer SKU</th>
                <th>Product</th>
                <th>Ordered quantity</th>
                <th>Confirmed quantity</th>
                <th>Received quantity</th>
                <th>Unit price</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->manufacturer_sku }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->ordered_quantity }}</td>
                    <td>{{ $item->confirmed_quantity }}</td>
                    <td>{{ $item->received_quantity }}</td>
                    <td>{{ $item->unit_price }}</td>
                    <td>{{ $item->currency }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
