<section>
    <h2>Receiving Discrepancies</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>SKU</th>
                <th>Expected</th>
                <th>Received</th>
                <th>Damaged</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($record->receiving_discrepancies_json ?? [] as $discrepancy)
                <tr>
                    <td>{{ $discrepancy['type'] ?? '' }}</td>
                    <td>{{ $discrepancy['sku'] ?? '' }}</td>
                    <td>{{ $discrepancy['expected_quantity'] ?? '' }}</td>
                    <td>{{ $discrepancy['received_quantity'] ?? '' }}</td>
                    <td>{{ $discrepancy['damaged_quantity'] ?? '' }}</td>
                    <td>{{ $discrepancy['message'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No receiving discrepancies.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
