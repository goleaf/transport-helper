@extends('layouts.app')

@section('title')
Procurement Policy
@endsection

@section('content')
<x-supply.page-header :title="$policy->name" subtitle="Deterministic procurement gate configuration." :status="$policy->status" :back-url="route('supply.procurement.policies.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="button-row">
        <x-supply.button :href="route('supply.procurement.policies.edit', $policy)" mode="outline">Edit policy</x-supply.button>
        <form method="POST" action="{{ route('supply.procurement.policies.archive', $policy) }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="reason" value="Archived from procurement policy detail page.">
            <x-supply.button type="submit" mode="outline" variant="warning">Archive</x-supply.button>
        </form>
    </div>
</section>

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $policy->company?->name }}</dd>
        <dt>Mode</dt>
        <dd>{{ ucfirst($policy->enforcement_mode?->value ?? $policy->enforcement_mode) }}</dd>
        <dt>Default currency</dt>
        <dd>{{ $policy->default_currency }}</dd>
        <dt>Company default</dt>
        <dd>{{ $policy->is_default ? 'Yes' : 'No' }}</dd>
        <dt>Created by</dt>
        <dd>{{ $policy->createdBy?->name ?? 'System' }}</dd>
        <dt>Updated by</dt>
        <dd>{{ $policy->updatedBy?->name ?? 'System' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Policy rules</p>
            <h2>Readable rule summary</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <tbody>
            @forelse ($ruleSummary as $row)
                <tr>
                    <th>{{ $row['label'] }}</th>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @empty
                <tr>
                    <td>No rule summary available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
