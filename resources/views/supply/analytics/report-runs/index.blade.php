@extends('layouts.app')

@section('title')
Analytics Report Runs
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Analytics</p>
        <h1>Analytics Report Runs</h1>
    </div>
    <a href="{{ route('supply.analytics.dashboard') }}">Back to analytics</a>
</header>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>ID</th>
            <th>Report</th>
            <th>Status</th>
            <th>Started by</th>
            <th>Started</th>
            <th>Finished</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($runs as $run)
            <tr>
                <td>{{ $run->id }}</td>
                <td>{{ $run->report_type }}</td>
                <td>{{ $run->status->value }}</td>
                <td>{{ $run->startedBy?->name ?? 'System' }}</td>
                <td>{{ $run->started_at }}</td>
                <td>{{ $run->finished_at }}</td>
                <td><x-supply.table-action :href="route('supply.analytics.report-runs.show', $run)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No report runs yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $runs->links() }}
@endsection
