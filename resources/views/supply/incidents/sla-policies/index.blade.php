@extends('layouts.app')

@section('title')
Incident SLA Policies
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Incident configuration</p>
        <h1>Incident SLA Policies</h1>
    </div>
    <nav aria-label="SLA links">
        <a href="{{ route('supply.incidents.index') }}">Back to incidents</a>
        <a href="{{ route('supply.incidents.sla-policies.create') }}">Create policy</a>
    </nav>
</header>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Priority</th>
                        <th>Response</th>
                        <th>Resolution</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr>
                            <td>{{ $policy->name }}</td>
                            <td>{{ $policy->company?->name ?? 'Global' }}</td>
                            <td>{{ $policy->incident_type ?: 'Any' }}</td>
                            <td>{{ $policy->severity ?? 'Any' }}</td>
                            <td>{{ $policy->priority ?? 'Any' }}</td>
                            <td>{{ $policy->response_minutes }} minutes</td>
                            <td>{{ $policy->resolution_minutes }} minutes</td>
                            <td>{{ $policy->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No custom SLA policies. Defaults are active.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $policies->links() }}
    </div>
</section>
@endsection
