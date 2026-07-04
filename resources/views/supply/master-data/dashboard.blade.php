@extends('layouts.app')

@section('title')
Master Data Governance
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Supplier And Product Master Data</h1>
    </div>
    <x-supply.button :href="route('supply.master-data.reports.quality')">Open quality report</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<x-supply.alert tone="warning">
    Duplicate detection and unknown SKU resolution are review workflows. The system does not automatically merge records or create products.
</x-supply.alert>

<section class="grid gap-4 md:grid-cols-3">
    @foreach ($counts as $label => $value)
        <x-supply.card>
            <p class="portal-eyebrow"><x-supply.human-label :label="$label" /></p>
            <strong>{{ $value }}</strong>
        </x-supply.card>
    @endforeach
</section>

<section>
    <h2>Recent Data Quality Issues</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Severity</th>
                <th>Message</th>
                <th>Recommended action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentIssues as $issue)
                <tr>
                    <td><x-supply.human-label :label="$issue['type']" /></td>
                    <td><x-supply.status-badge :status="$issue['severity']" /></td>
                    <td>{{ $issue['message'] }}</td>
                    <td>{{ $issue['recommended_action'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No current quality issues for the selected company.</td>
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
            @forelse ($recentDuplicateSuggestions as $suggestion)
                <tr>
                    <td><x-supply.human-label :label="$suggestion['type']" /></td>
                    <td>{{ $suggestion['source_id'] }}</td>
                    <td>{{ $suggestion['target_id'] }}</td>
                    <td>{{ $suggestion['score'] }}</td>
                    <td>{{ $suggestion['message'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No duplicate suggestions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
