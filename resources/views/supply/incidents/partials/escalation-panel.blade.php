@props(['incident'])

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Escalations</h2>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Escalated to</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incident->escalations as $escalation)
                        <tr>
                            <td>{{ $escalation->escalation_level }}</td>
                            <td>{{ $escalation->escalatedTo?->name ?? 'Manager/admin fallback' }}</td>
                            <td>{{ $escalation->status_label }}</td>
                            <td>{{ $escalation->reason }}</td>
                            <td>{{ $escalation->escalated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No escalations recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
