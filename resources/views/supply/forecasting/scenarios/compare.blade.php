@extends('layouts.app')

@section('title')
Scenario Comparison
@endsection

@section('content')
<x-supply.page-header title="Scenario Comparison" :subtitle="$scenarioA->name.' compared with '.$scenarioB->name" :back-url="route('supply.forecasting.scenarios.index')" />

@include('supply.forecasting.partials.warnings', ['warnings' => $comparison['warnings']])

@include('supply.forecasting.partials.explanation-panel', ['title' => 'Comparison Summary', 'value' => $comparison['summary']])

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Quantity delta</p>
            <h2>Compared items</h2>
        </div>
    </div>

    @include('supply.forecasting.partials.comparison-table', ['comparison' => $comparison])
</section>
@endsection
