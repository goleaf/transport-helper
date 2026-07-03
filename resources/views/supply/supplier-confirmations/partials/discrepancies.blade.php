<section>
    <h2>Discrepancies</h2>
    <p>{{ $confirmation->discrepancy_summary ?? 'No discrepancy summary.' }}</p>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Severity</th>
                <th>SKU</th>
                <th>Ordered</th>
                <th>Confirmed</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($confirmation->discrepancies_json ?? []) as $discrepancy)
                <tr>
                    <td>{{ $discrepancy['type'] ?? '' }}</td>
                    <td>{{ $discrepancy['severity'] ?? '' }}</td>
                    <td>{{ $discrepancy['sku'] ?? '' }}</td>
                    <td>{{ $discrepancy['ordered_quantity'] ?? '' }}</td>
                    <td>{{ $discrepancy['confirmed_quantity'] ?? '' }}</td>
                    <td>{{ $discrepancy['message'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No discrepancies.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
