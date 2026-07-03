@props(['comparison'])

<table class="table table-zebra">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>First quantity</th>
            <th>Second quantity</th>
            <th>Difference</th>
            <th>Difference percent</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($comparison['rows'] as $row)
            <tr>
                <td>{{ $row['sku'] }}</td>
                <td>{{ $row['product_name'] }}</td>
                <td>{{ $row['a_quantity'] }}</td>
                <td>{{ $row['b_quantity'] }}</td>
                <td>{{ $row['difference'] }}</td>
                <td>{{ $row['difference_percent'] ?? 'Not available' }}</td>
                <td>{{ $row['reason_summary'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No comparison rows.</td>
            </tr>
        @endforelse
    </tbody>
</table>
