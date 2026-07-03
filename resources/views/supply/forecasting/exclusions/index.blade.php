@extends('layouts.app')

@section('title')
Sales Exclusion Rules
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Forecast refinement</p>
        <h1>Sales Exclusion Rules</h1>
    </div>
    <x-supply.button :href="route('supply.forecasting.exclusions.create')">Create exclusion</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<nav class="tabs tabs-box">
    <a class="tab" href="{{ route('supply.forecasting.scenarios.index') }}">Scenarios</a>
    <a class="tab" href="{{ route('supply.forecasting.profiles.index') }}">Profiles</a>
    <a class="tab tab-active" href="{{ route('supply.forecasting.exclusions.index') }}">Exclusions</a>
    <a class="tab" href="{{ route('supply.forecasting.overrides.index') }}">Overrides</a>
</nav>

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Rule</th>
                <th>Scope</th>
                <th>Dates</th>
                <th>Applies to</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rules as $rule)
                <tr>
                    <td>{{ $rule->rule_type->value }}</td>
                    <td>
                        <strong>{{ $rule->product?->sku ?? $rule->category ?? $rule->supplier?->name ?? 'Company default' }}</strong>
                        <span>{{ $rule->company?->name }}</span>
                    </td>
                    <td>{{ $rule->date_from?->toDateString() }} to {{ $rule->date_to?->toDateString() }}</td>
                    <td>{{ $rule->applies_to }}</td>
                    <td>{{ $rule->reason }}</td>
                    <td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td>
                    <td><x-supply.table-action :href="route('supply.forecasting.exclusions.show', $rule)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No sales exclusion rules.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $rules->links() }}
</section>
@endsection
