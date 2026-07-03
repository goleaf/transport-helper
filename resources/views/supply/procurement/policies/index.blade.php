@extends('layouts.app')

@section('title')
Procurement Policies
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Procurement Policies</h1>
    </div>
    <x-supply.button :href="route('supply.procurement.policies.create')">Create policy</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Mode</th>
                <th>Currency</th>
                <th>Status</th>
                <th>Default</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($policies as $policy)
                <tr>
                    <td><strong>{{ $policy->name }}</strong></td>
                    <td>{{ $policy->company?->name }}</td>
                    <td>{{ ucfirst($policy->enforcement_mode?->value ?? $policy->enforcement_mode) }}</td>
                    <td>{{ $policy->default_currency }}</td>
                    <td><x-supply.status-badge :status="$policy->status" /></td>
                    <td>{{ $policy->is_default ? 'Yes' : 'No' }}</td>
                    <td>{{ $policy->updated_at?->diffForHumans() }}</td>
                    <td><x-supply.table-action :href="route('supply.procurement.policies.show', $policy)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No procurement policies yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $policies->links() }}
</section>
@endsection
