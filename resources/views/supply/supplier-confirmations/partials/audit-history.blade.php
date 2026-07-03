<section>
    <h2>Audit history</h2>
    <ul>
        @forelse ($auditLogs as $auditLog)
            <li>{{ $auditLog->created_at?->toDateTimeString() }} {{ $auditLog->event_type }} {{ $auditLog->user?->name }}</li>
        @empty
            <li>No audit logs.</li>
        @endforelse
    </ul>
</section>
