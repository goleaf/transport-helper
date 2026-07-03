<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Order documents</p>
            <h2>Exports</h2>
        </div>
    </div>

    @if ($canExport)
        <form method="post" action="{{ route('supply.supplier-orders.export', $order) }}">
            @csrf
            <div class="actions">
                @foreach ($exportFormats as $format => $label)
                    <x-supply.button type="submit" name="format" value="{{ $format }}" mode="outline" variant="neutral">{{ $label }}</x-supply.button>
                @endforeach
            </div>
        </form>
    @endif

    <table class="table table-zebra">
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
                    <td>{{ $exportTypeLabels[$exportFile->id] ?? 'Export file' }}</td>
                    <td><x-supply.status-badge :status="$exportFile->status" /></td>
                    <td>{{ $exportFile->createdBy?->name ?? 'System' }}</td>
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
