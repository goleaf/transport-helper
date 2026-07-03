<table class="table table-zebra">
    <thead>
        <tr>
            @forelse ($columns as $column)
                <th>{{ $column['label'] }}</th>
            @empty
                <th>Report</th>
            @endforelse
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                @forelse ($columns as $column)
                    <td>{{ $row[$column['key']] ?? 'Not set' }}</td>
                @empty
                    <td>No columns configured.</td>
                @endforelse
            </tr>
        @empty
            <tr>
                <td colspan="12">No rows for this report.</td>
            </tr>
        @endforelse
    </tbody>
</table>
