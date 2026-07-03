<section>
    <h2>Exports</h2>

    @if ($canExport)
        <form method="post" action="{{ route('supply.supplier-orders.export', $order) }}">
            @csrf
            <button type="submit" name="format" value="csv">Export spreadsheet</button>
            <button type="submit" name="format" value="json">Export structured data</button>
            <button type="submit" name="format" value="excel_csv">Export Excel spreadsheet</button>
            <button type="submit" name="format" value="pdf">Export PDF draft</button>
            <button type="submit" name="format" value="supplier_custom_template">Export supplier template draft</button>
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
                    <td><x-supply.human-label :value="$exportFile->export_type" /></td>
                    <td><x-supply.status-badge :status="$exportFile->status" /></td>
                    <td>{{ $exportFile->createdBy?->name }}</td>
                    <td>{{ $exportFile->created_at?->toDateTimeString() }}</td>
                    <td><x-supply.table-action :href="route('supply.exports.download', $exportFile)" label="Download" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No exports.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
