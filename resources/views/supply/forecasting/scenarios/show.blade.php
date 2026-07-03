@extends('layouts.app')

@section('title')
Calculation Scenario
@endsection

@section('content')
<x-supply.page-header :title="$scenario->name" subtitle="Deterministic simulation result" :status="$scenario->status" :back-url="route('supply.forecasting.scenarios.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<x-supply.alert tone="info">This scenario is not an approval. It did not create supplier orders or mutate existing proposals.</x-supply.alert>

@include('supply.forecasting.partials.warnings', ['warnings' => $scenario->warnings_json ?? []])

<section>
    <div class="button-row">
        <form method="POST" action="{{ route('supply.forecasting.scenarios.export', $scenario) }}">
            @csrf
            <input type="hidden" name="format" value="csv">
            <x-supply.button type="submit" mode="outline">Export CSV</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.forecasting.scenarios.export', $scenario) }}">
            @csrf
            <input type="hidden" name="format" value="json">
            <x-supply.button type="submit" mode="outline">Export detail file</x-supply.button>
        </form>
    </div>
</section>

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $scenario->company?->name }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $scenario->supplier?->name ?? 'Company-wide' }}</dd>
        <dt>Formula version</dt>
        <dd>{{ $scenario->formula_version }}</dd>
        <dt>Mode</dt>
        <dd>{{ $scenario->simulation_mode->value }}</dd>
        <dt>Simulated at</dt>
        <dd>{{ $scenario->simulated_at?->toDateTimeString() ?? 'Not simulated' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Scenario items</p>
            <h2>Recommended quantity changes</h2>
        </div>
    </div>

    @include('supply.forecasting.partials.scenario-items-table', ['items' => $scenario->items])
</section>

@include('supply.forecasting.partials.explanation-panel', ['title' => 'Scenario Summary', 'value' => $scenario->summary_json ?? []])
@endsection
