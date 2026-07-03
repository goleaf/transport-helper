@extends('layouts.app')

@section('title')
{{ $report['title'] ?? 'Analytics Report' }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Analytics report</p>
        <h1>{{ $report['title'] ?? 'Analytics Report' }}</h1>
        <p>{{ $report['description'] ?? 'Read-only analytics report.' }}</p>
    </div>
    <a href="{{ route('supply.analytics.dashboard') }}">Back to analytics</a>
</header>

@include('supply.analytics.partials.report-filters', ['reportType' => $reportType, 'filters' => $report['filters'] ?? []])
@include('supply.analytics.partials.warnings', ['warnings' => $report['warnings'] ?? []])

<section class="guardrail-grid">
    @forelse ($summaryCards as $card)
        @include('supply.analytics.partials.kpi-card', ['card' => $card])
    @empty
        <p>No summary metrics available.</p>
    @endforelse
</section>

@include('supply.analytics.partials.export-panel', ['reportType' => $reportType, 'filters' => $report['filters'] ?? []])
@include('supply.analytics.partials.saved-report-panel', ['reportType' => $reportType, 'filters' => $report['filters'] ?? []])

<section>
    <h2>Rows</h2>
    @include('supply.analytics.partials.report-table', ['table' => $reportRowsTable])
</section>

<section>
    <h2>KPI Definitions</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Formula</th>
                <th>Limitations</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($definitionRows as $definition)
                <tr>
                    <td>{{ $definition['name'] }}</td>
                    <td>{{ $definition['formula'] }}</td>
                    <td>{{ $definition['limitations'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No KPI definitions available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
