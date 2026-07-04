@extends('layouts.app')

@section('title')
Master Data Quality Report
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Quality Report</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section>
    <form method="POST" action="{{ route('supply.master-data.reports.quality.export') }}">
        @csrf
        <input type="hidden" name="company_id" value="{{ $company?->id }}">
        <input type="hidden" name="format" value="csv">
        <x-supply.button type="submit">Export CSV</x-supply.button>
    </form>
</section>

<section>
    <h2>Summary</h2>
    <table class="table table-zebra">
        <tbody>
            @forelse ($summaryRows as $row)
                <tr>
                    <th>{{ $row['label'] }}</th>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @empty
                <tr>
                    <td>No company is available for report generation.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Issues</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Severity</th>
                <th>Object</th>
                <th>Message</th>
                <th>Recommended action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($issues as $issue)
                <tr>
                    <td><x-supply.human-label :label="$issue['type']" /></td>
                    <td><x-supply.status-badge :status="$issue['severity']" /></td>
                    <td>{{ $issue['object_id'] }}</td>
                    <td>{{ $issue['message'] }}</td>
                    <td>{{ $issue['recommended_action'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No quality issues found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Duplicate Suggestions</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Source</th>
                <th>Target</th>
                <th>Score</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($duplicateSuggestions as $suggestion)
                <tr>
                    <td><x-supply.human-label :label="$suggestion['type']" /></td>
                    <td>{{ $suggestion['source_id'] }}</td>
                    <td>{{ $suggestion['target_id'] }}</td>
                    <td>{{ $suggestion['score'] }}</td>
                    <td>{{ $suggestion['message'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No duplicate suggestions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
