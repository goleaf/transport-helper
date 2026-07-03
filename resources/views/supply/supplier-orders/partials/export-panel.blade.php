<section>
    <h2>Exports</h2>

    @if ($canExport)
        <form method="post" action="{{ route('supply.supplier-orders.export', $order) }}">
            @csrf
            <button type="submit" name="format" value="csv">Export CSV</button>
            <button type="submit" name="format" value="json">Export JSON</button>
            <button type="submit" name="format" value="excel_csv">Export Excel CSV</button>
            <button type="submit" name="format" value="pdf">PDF export placeholder</button>
            <button type="submit" name="format" value="supplier_custom_template">Supplier custom template placeholder</button>
        </form>
    @endif

    <table>
        <thead>
            <tr>
                <th>Filename</th>
                <th>Type</th>
                <th>Status</th>
                <th>Created by</th>
                <th>Created at</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($exportFiles as $exportFile)
                <tr>
                    <td>{{ $exportFile->filename }}</td>
                    <td>{{ $exportFile->export_type }}</td>
                    <td>{{ $exportFile->status }}</td>
                    <td>{{ $exportFile->createdBy?->name }}</td>
                    <td>{{ $exportFile->created_at?->toDateTimeString() }}</td>
                    <td><a href="{{ route('supply.exports.download', $exportFile) }}">Download</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No exports.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
