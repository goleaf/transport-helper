@extends('layouts.app')

@section('title')
Management Analytics
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Read-only reporting</p>
        <h1>Management Analytics</h1>
    </div>
    <nav aria-label="Analytics links">
        <a href="{{ route('supply.analytics.saved-reports.index') }}">Saved reports</a>
        <a href="{{ route('supply.analytics.report-runs.index') }}">Report runs</a>
    </nav>
</header>

@include('supply.analytics.partials.warnings', ['warnings' => $dashboard['warnings'] ?? []])

<section class="guardrail-grid">
    @forelse ($summaryCards as $card)
        @include('supply.analytics.partials.kpi-card', ['card' => $card])
    @empty
        <article class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h2>No analytics yet</h2>
                <p>Run imports and workflow actions before management analytics becomes meaningful.</p>
            </div>
        </article>
    @endforelse
</section>

<section>
    <h2>Detailed Reports</h2>
    <div class="guardrail-grid">
        @forelse ($reportLinks as $reportLink)
            <article class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h3>{{ $reportLink['label'] }}</h3>
                    <p>Read-only management report.</p>
                    <x-supply.button :href="route('supply.analytics.reports.show', ['reportType' => $reportLink['type']])" mode="outline" variant="neutral">Open report</x-supply.button>
                </div>
            </article>
        @empty
            <p>No report definitions available.</p>
        @endforelse
    </div>
</section>

<section>
    <h2>Top Stockout Risks</h2>
    @include('supply.analytics.partials.report-table', ['table' => $topRisksTable])
</section>
@endsection
