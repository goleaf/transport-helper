@extends('layouts.app')

@section('title')
Edit Procurement Policy
@endsection

@section('content')
<x-supply.page-header :title="$policy->name" subtitle="Edit procurement policy." :back-url="route('supply.procurement.policies.show', $policy)" />

@include('supply.procurement.partials.tabs')

<section>
    <form method="POST" action="{{ route('supply.procurement.policies.update', $policy) }}" class="form-grid">
        @csrf
        @method('PATCH')

        @include('supply.procurement.partials.policy-form', ['policy' => $policy])

        <div class="form-actions">
            <x-supply.button type="submit">Save policy</x-supply.button>
        </div>
    </form>
</section>
@endsection
