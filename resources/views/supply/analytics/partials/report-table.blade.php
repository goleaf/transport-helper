<table class="table table-zebra">
    <thead>
        <tr>
            @forelse ($table['headers'] as $header)
                <th>{{ $header['label'] }}</th>
            @empty
                <th>Message</th>
            @endforelse
        </tr>
    </thead>
    <tbody>
        @forelse ($table['rows'] as $row)
            <tr>
                @forelse ($row['cells'] as $cell)
                    <td>{{ $cell }}</td>
                @empty
                    <td>No rows available.</td>
                @endforelse
            </tr>
        @empty
            <tr>
                <td colspan="{{ $table['empty_colspan'] }}">No rows available.</td>
            </tr>
        @endforelse
    </tbody>
</table>
