@extends('layouts.app')

@section('title')
Replenishment Profiles
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Forecast refinement</p>
        <h1>Replenishment Profiles</h1>
    </div>
    <x-supply.button :href="route('supply.forecasting.profiles.create')">Create profile</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<nav class="tabs tabs-box">
    <a class="tab" href="{{ route('supply.forecasting.scenarios.index') }}">Scenarios</a>
    <a class="tab tab-active" href="{{ route('supply.forecasting.profiles.index') }}">Profiles</a>
    <a class="tab" href="{{ route('supply.forecasting.exclusions.index') }}">Exclusions</a>
    <a class="tab" href="{{ route('supply.forecasting.overrides.index') }}">Overrides</a>
</nav>

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Scope</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Rules</th>
                <th>Created by</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($profiles as $profile)
                <tr>
                    <td><strong>{{ $profile->name }}</strong></td>
                    <td>
                        <strong>{{ $profile->product?->sku ?? $profile->category ?? $profile->supplier?->name ?? 'Company default' }}</strong>
                        <span>{{ $profile->company?->name }}</span>
                    </td>
                    <td>{{ $profile->priority }}</td>
                    <td><x-supply.status-badge :status="$profile->status" /></td>
                    <td>
                        <span>Promotions: {{ $profile->exclude_promotions ? 'excluded' : 'included' }}</span>
                        <span>Anomalies: {{ $profile->exclude_anomalies ? 'excluded' : 'included' }}</span>
                        <span>Seasonality: {{ $profile->seasonality_enabled ? 'enabled' : 'disabled' }}</span>
                    </td>
                    <td>{{ $profile->createdBy?->name ?? 'System' }}</td>
                    <td><x-supply.table-action :href="route('supply.forecasting.profiles.show', $profile)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No replenishment profiles.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $profiles->links() }}
</section>
@endsection
