@extends('layouts.app')

@section('title')
Analytics Report Run #{{ $run->id }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Analytics run</p>
        <h1>Report Run #{{ $run->id }}</h1>
    </div>
    <a href="{{ route('supply.analytics.report-runs.index') }}">Back to report runs</a>
</header>

<dl>
    <dt>Report type</dt>
    <dd>{{ $run->report_type }}</dd>
    <dt>Status</dt>
    <dd>{{ $run->status->value }}</dd>
    <dt>Started by</dt>
    <dd>{{ $run->startedBy?->name ?? 'System' }}</dd>
    <dt>Started</dt>
    <dd>{{ $run->started_at }}</dd>
    <dt>Finished</dt>
    <dd>{{ $run->finished_at }}</dd>
</dl>

@include('supply.analytics.partials.warnings', ['warnings' => $run->warnings_json ?? []])

<section>
    <h2>Result Summary</h2>
    @include('supply.analytics.partials.report-table', ['table' => $summaryTable])
</section>
@endsection
