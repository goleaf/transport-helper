<section>
    <h2>Audit History</h2>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Event</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->created_at?->toDateTimeString() }}</td>
                    <td>{{ $auditLog->event_type }}</td>
                    <td>{{ $auditLog->user?->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No audit logs.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
