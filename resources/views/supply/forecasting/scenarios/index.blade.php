@extends('layouts.app')

@section('title')
Calculation Scenarios
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Forecast refinement</p>
        <h1>Calculation Scenarios</h1>
    </div>
    <x-supply.button :href="route('supply.forecasting.scenarios.create')">Run scenario</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<x-supply.alert tone="info">Scenario simulation does not approve proposals, create supplier orders, send email, select carriers or update logistics.</x-supply.alert>

<nav class="tabs tabs-box">
    <a class="tab tab-active" href="{{ route('supply.forecasting.scenarios.index') }}">Scenarios</a>
    <a class="tab" href="{{ route('supply.forecasting.profiles.index') }}">Profiles</a>
    <a class="tab" href="{{ route('supply.forecasting.exclusions.index') }}">Exclusions</a>
    <a class="tab" href="{{ route('supply.forecasting.overrides.index') }}">Overrides</a>
</nav>

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Mode</th>
                <th>Items</th>
                <th>Summary</th>
                <th>Simulated at</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scenarios as $scenario)
                <tr>
                    <td><strong>{{ $scenario->name }}</strong></td>
                    <td>{{ $scenario->supplier?->name ?? 'Company-wide' }}</td>
                    <td><x-supply.status-badge :status="$scenario->status" /></td>
                    <td>{{ $scenario->simulation_mode->value }}</td>
                    <td>{{ $scenario->items_count }}</td>
                    <td>
                        <span>Total: {{ $scenario->summary_json['total_simulated_quantity'] ?? 'Not simulated' }}</span>
                        <span>Review: {{ $scenario->summary_json['needs_review_count'] ?? 0 }}</span>
                    </td>
                    <td>{{ $scenario->simulated_at?->toDateTimeString() ?? 'Not simulated' }}</td>
                    <td><x-supply.table-action :href="route('supply.forecasting.scenarios.show', $scenario)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No calculation scenarios.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $scenarios->links() }}
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Scenario comparison</p>
            <h2>Compare two scenario variants</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.forecasting.scenarios.compare') }}" class="form-grid">
        @csrf

        <label>
            <span>First scenario</span>
            <select class="select select-bordered" name="scenario_a_id" required>
                @forelse ($scenarios as $scenario)
                    <option value="{{ $scenario->id }}">{{ $scenario->name }}</option>
                @empty
                    <option value="">No scenarios</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Second scenario</span>
            <select class="select select-bordered" name="scenario_b_id" required>
                @forelse ($scenarios as $scenario)
                    <option value="{{ $scenario->id }}">{{ $scenario->name }}</option>
                @empty
                    <option value="">No scenarios</option>
                @endforelse
            </select>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit" mode="outline">Compare scenarios</x-supply.button>
        </div>
    </form>
</section>
@endsection
