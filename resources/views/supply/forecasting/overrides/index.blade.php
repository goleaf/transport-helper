@extends('layouts.app')

@section('title')
Trend Overrides
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Forecast refinement</p>
        <h1>Trend Overrides</h1>
    </div>
    <x-supply.button :href="route('supply.forecasting.overrides.create')">Create override</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<nav class="tabs tabs-box">
    <a class="tab" href="{{ route('supply.forecasting.scenarios.index') }}">Scenarios</a>
    <a class="tab" href="{{ route('supply.forecasting.profiles.index') }}">Profiles</a>
    <a class="tab" href="{{ route('supply.forecasting.exclusions.index') }}">Exclusions</a>
    <a class="tab tab-active" href="{{ route('supply.forecasting.overrides.index') }}">Overrides</a>
</nav>

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Scope</th>
                <th>Trend value</th>
                <th>Dates</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Approval</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($overrides as $override)
                <tr>
                    <td>
                        <strong>{{ $override->product?->sku ?? $override->category ?? $override->supplier?->name ?? 'Company default' }}</strong>
                        <span>{{ $override->company?->name }}</span>
                    </td>
                    <td>{{ $override->trend_value }}</td>
                    <td>{{ $override->date_from?->toDateString() }} to {{ $override->date_to?->toDateString() }}</td>
                    <td><x-supply.status-badge :status="$override->status" /></td>
                    <td>{{ $override->reason }}</td>
                    <td>{{ $override->approvedBy?->name ?? 'Not approved' }}</td>
                    <td><x-supply.table-action :href="route('supply.forecasting.overrides.show', $override)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No trend overrides.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $overrides->links() }}
</section>
@endsection
