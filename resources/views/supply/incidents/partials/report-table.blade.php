@props(['rows'])

<div class="overflow-x-auto">
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Incident</th>
                <th>Title</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>SLA</th>
                <th>Owner</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['incident_number'] ?? 'n/a' }}</td>
                    <td>{{ $row['title'] ?? 'n/a' }}</td>
                    <td>{{ $row['type'] ?? 'n/a' }}</td>
                    <td>{{ $row['severity'] ?? 'n/a' }}</td>
                    <td>{{ $row['status'] ?? 'n/a' }}</td>
                    <td>{{ $row['sla_status'] ?? 'within_sla' }}</td>
                    <td>{{ $row['owner'] ?? 'Unassigned' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No incident rows match the filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
