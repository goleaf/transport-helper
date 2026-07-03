@extends('layouts.app')

@section('title')
Operational Incidents
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Exception management</p>
        <h1>Operational Incidents</h1>
        <p>Incident management does not perform the workflow action automatically.</p>
    </div>
    <nav aria-label="Incident actions">
        <a href="{{ route('supply.incidents.create') }}">Create incident</a>
        <a href="{{ route('supply.incidents.reports.index') }}">Reports</a>
        <a href="{{ route('supply.incidents.sla-policies.index') }}">SLA policies</a>
    </nav>
</header>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="guardrail-grid">
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Open critical</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['open_by_severity']['critical'] ?? 0 }}</p>
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>SLA breached</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['sla_breaches'] ?? 0 }}</p>
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Total incidents</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['total_incidents'] ?? 0 }}</p>
        </div>
    </article>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Filters</h2>
        <form method="GET" action="{{ route('supply.incidents.index') }}" class="grid gap-3 md:grid-cols-4">
            <label class="form-control">
                <span class="label-text">Status</span>
                <select class="select select-bordered" name="status">
                    <option value="">Any</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Severity</span>
                <select class="select select-bordered" name="severity">
                    <option value="">Any</option>
                    @foreach ($severities as $severity)
                        <option value="{{ $severity }}" @selected(($filters['severity'] ?? null) === $severity)>{{ $severity }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Type</span>
                <select class="select select-bordered" name="type">
                    <option value="">Any</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">SLA</span>
                <select class="select select-bordered" name="sla_status">
                    <option value="">Any</option>
                    @foreach ($slaStatuses as $slaStatus)
                        <option value="{{ $slaStatus }}" @selected(($filters['sla_status'] ?? null) === $slaStatus)>{{ $slaStatus }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Owner</span>
                <select class="select select-bordered" name="assigned_user_id">
                    <option value="">Any</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['assigned_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Source type</span>
                <select class="select select-bordered" name="source_type">
                    <option value="">Any</option>
                    @foreach ($sourceTypes as $sourceType)
                        <option value="{{ $sourceType }}" @selected(($filters['source_type'] ?? null) === $sourceType)>{{ $sourceType }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Date from</span>
                <input class="input input-bordered" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label class="form-control">
                <span class="label-text">Date to</span>
                <input class="input input-bordered" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <x-supply.button type="submit" class="md:col-span-4">Apply filters</x-supply.button>
        </form>
    </div>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Incident queue</h2>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Incident</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>SLA</th>
                        <th>Owner</th>
                        <th>Source</th>
                        <th>Last seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr>
                            <td><x-supply.button :href="route('supply.incidents.show', $incident)" size="sm" mode="link">{{ $incident->incident_number }}</x-supply.button></td>
                            <td>{{ $incident->title }}</td>
                            <td>{{ $incident->incident_type_label }}</td>
                            <td>@include('supply.incidents.partials.severity-badge', ['label' => $incident->severity_label, 'tone' => $incident->severity_tone])</td>
                            <td>{{ $incident->priority->value }}</td>
                            <td>@include('supply.incidents.partials.status-badge', ['label' => $incident->status_label, 'tone' => $incident->status_tone])</td>
                            <td>@include('supply.incidents.partials.sla-badge', ['label' => $incident->sla_status_label, 'tone' => $incident->sla_tone])</td>
                            <td>{{ $incident->assignedUser?->name ?? 'Unassigned' }}</td>
                            <td>@include('supply.incidents.partials.workflow-link', ['incident' => $incident])</td>
                            <td>{{ $incident->last_seen_at?->format('Y-m-d H:i') ?? $incident->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">No incidents match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $incidents->links() }}
    </div>
</section>
@endsection
