@extends('layouts.app')

@section('title')
Create Procurement Policy
@endsection

@section('content')
<x-supply.page-header title="Create Procurement Policy" subtitle="Approval thresholds, budgets and supplier rule mode." :back-url="route('supply.procurement.policies.index')" />

@include('supply.procurement.partials.tabs')

<section>
    <form method="POST" action="{{ route('supply.procurement.policies.store') }}" class="form-grid">
        @csrf

        @include('supply.procurement.partials.policy-form', ['policy' => null])

        <div class="form-actions">
            <x-supply.button type="submit">Create policy</x-supply.button>
        </div>
    </form>
</section>
@endsection
