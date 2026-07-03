@extends('layouts.app')

@section('title')
Saved Analytics Reports
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Analytics</p>
        <h1>Saved Analytics Reports</h1>
    </div>
    <a href="{{ route('supply.analytics.dashboard') }}">Back to analytics</a>
</header>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>Name</th>
            <th>Report type</th>
            <th>Shared</th>
            <th>Default</th>
            <th>Created</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reports as $report)
            <tr>
                <td>{{ $report->name }}</td>
                <td>{{ $report->report_type }}</td>
                <td>{{ $report->is_shared ? 'Yes' : 'No' }}</td>
                <td>{{ $report->is_default ? 'Yes' : 'No' }}</td>
                <td>{{ $report->created_at }}</td>
                <td>
                    <x-supply.table-action :href="route('supply.analytics.reports.show', ['reportType' => $report->report_type] + ($report->filters_json ?? []))" label="Open" />
                    <form method="POST" action="{{ route('supply.analytics.saved-reports.default', $report) }}">
                        @csrf
                        <x-supply.button type="submit" size="sm" mode="outline" variant="neutral">Set default</x-supply.button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No saved reports yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
