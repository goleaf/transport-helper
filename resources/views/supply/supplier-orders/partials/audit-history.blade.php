<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Traceability</p>
            <h2>Audit history</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Event</th>
                <th>User</th>
                <th>Details</th>
                <th>Created at</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->event_type }}</td>
                    <td>{{ $auditLog->user?->name ?? 'System' }}</td>
                    <td><x-supply.structured-value :value="$auditLog->metadata_json" /></td>
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
