@extends('layouts.app')

@section('title')
Run Calculation Scenario
@endsection

@section('content')
<x-supply.page-header title="Run Calculation Scenario" subtitle="Build refined deterministic inputs and compare the output before any order action." :back-url="route('supply.forecasting.scenarios.index')" />

<x-supply.alert tone="info">Running a scenario creates scenario records and audit events only. It does not mutate proposals, supplier orders, email workflow, carrier selection or logistics.</x-supply.alert>

<section>
    <form method="POST" action="{{ route('supply.forecasting.scenarios.simulate') }}" class="form-grid">
        @csrf

        <label>
            <span>Company</span>
            <select class="select select-bordered" name="company_id" required>
                @forelse ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
                @empty
                    <option value="">No companies</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Supplier</span>
            <select class="select select-bordered" name="supplier_id" required>
                @forelse ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @empty
                    <option value="">No suppliers</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Name</span>
            <input class="input input-bordered" name="name" value="{{ old('name', 'Forecast refinement scenario') }}" required>
        </label>

        <label>
            <span>Category</span>
            <input class="input input-bordered" name="category" value="{{ old('category') }}">
        </label>

        <label>
            <span>Products</span>
            <select class="select select-bordered" name="product_ids[]" multiple>
                @forelse ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                @empty
                    <option value="">No products</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>T0 date</span>
            <input class="input input-bordered" type="date" name="t0_date" value="{{ old('t0_date', now()->toDateString()) }}" required>
        </label>

        <label>
            <span>T1 date</span>
            <input class="input input-bordered" type="date" name="t1_date" value="{{ old('t1_date', now()->addWeeks(2)->toDateString()) }}" required>
        </label>

        <label>
            <span>T2 date</span>
            <input class="input input-bordered" type="date" name="t2_date" value="{{ old('t2_date', now()->addWeeks(6)->toDateString()) }}" required>
        </label>

        <label>
            <span>T3 date</span>
            <input class="input input-bordered" type="date" name="t3_date" value="{{ old('t3_date', now()->addWeeks(9)->toDateString()) }}" required>
        </label>

        <label>
            <input type="hidden" name="scenario_options[exclude_promotions]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[exclude_promotions]" value="1" checked>
            <span>Exclude promotion sales</span>
        </label>

        <label>
            <input type="hidden" name="scenario_options[exclude_anomalies]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[exclude_anomalies]" value="1" checked>
            <span>Exclude anomaly sales</span>
        </label>

        <label>
            <input type="hidden" name="scenario_options[use_seasonality]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[use_seasonality]" value="1">
            <span>Use seasonality</span>
        </label>

        <label>
            <span>Seasonality mode</span>
            <select class="select select-bordered" name="scenario_options[seasonality_mode]">
                <option value="none">none</option>
                <option value="multiply_trend">multiply_trend</option>
                <option value="multiply_period_sales">multiply_period_sales</option>
            </select>
        </label>

        <label>
            <input type="hidden" name="scenario_options[use_manual_overrides]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[use_manual_overrides]" value="1" checked>
            <span>Use approved trend overrides</span>
        </label>

        <label>
            <input type="hidden" name="scenario_options[outlier_detection]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[outlier_detection]" value="1">
            <span>Detect outlier candidates</span>
        </label>

        <label>
            <input type="hidden" name="scenario_options[exclude_outlier_candidates]" value="0">
            <input class="checkbox" type="checkbox" name="scenario_options[exclude_outlier_candidates]" value="1">
            <span>Exclude outlier candidates</span>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Run scenario</x-supply.button>
        </div>
    </form>
</section>
@endsection
