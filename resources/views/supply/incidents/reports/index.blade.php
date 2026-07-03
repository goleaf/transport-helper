@extends('layouts.app')

@section('title')
Incident Reports
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Incident analytics</p>
        <h1>Incident Reports</h1>
        <p>Reports expose blockers, SLA breaches and corrective action accountability.</p>
    </div>
    <a href="{{ route('supply.incidents.index') }}">Back to incidents</a>
</header>

<section class="guardrail-grid">
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Total incidents</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['total_incidents'] ?? 0 }}</p>
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>SLA breaches</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['sla_breaches'] ?? 0 }}</p>
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Average resolution hours</h2>
            <p class="text-3xl font-semibold">{{ $report['summary']['average_resolution_hours'] ?? 'n/a' }}</p>
        </div>
    </article>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Export</h2>
        <form method="POST" action="{{ route('supply.incidents.reports.export') }}" class="flex gap-3">
            @csrf
            <x-supply.button type="submit" name="format" value="csv" mode="outline">Export CSV</x-supply.button>
            <x-supply.button type="submit" name="format" value="json" mode="outline">Export structured file</x-supply.button>
        </form>
    </div>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Open by severity</h2>
        <ul>
            @forelse ($report['summary']['open_by_severity'] ?? [] as $severity => $count)
                <li>{{ $severity }}: {{ $count }}</li>
            @empty
                <li>No open incidents.</li>
            @endforelse
        </ul>
    </div>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Rows</h2>
        @include('supply.incidents.partials.report-table', ['rows' => $report['rows'] ?? []])
    </div>
</section>
@endsection
