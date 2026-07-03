<section>
    <h2>Audit history</h2>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>User</th>
                <th>Metadata</th>
                <th>Created at</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->event_type }}</td>
                    <td>{{ $auditLog->user?->name }}</td>
                    <td><pre>{{ json_encode($auditLog->metadata_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                    <td>{{ $auditLog->created_at?->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No audit records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
