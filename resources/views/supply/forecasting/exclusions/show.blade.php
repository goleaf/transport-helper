@extends('layouts.app')

@section('title')
Sales Exclusion Rule
@endsection

@section('content')
<x-supply.page-header title="Sales Exclusion Rule" :subtitle="$rule->reason" :back-url="route('supply.forecasting.exclusions.index')">
    <x-slot:actions>
        <x-supply.button :href="route('supply.forecasting.exclusions.edit', $rule)" mode="outline">Edit</x-supply.button>
    </x-slot:actions>
</x-supply.page-header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $rule->company?->name }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $rule->supplier?->name ?? 'Any supplier' }}</dd>
        <dt>Product</dt>
        <dd>{{ $rule->product?->sku ?? 'Any product' }}</dd>
        <dt>Category</dt>
        <dd>{{ $rule->category ?? 'Any category' }}</dd>
        <dt>Rule type</dt>
        <dd>{{ $rule->rule_type->value }}</dd>
        <dt>Dates</dt>
        <dd>{{ $rule->date_from?->toDateString() }} to {{ $rule->date_to?->toDateString() }}</dd>
        <dt>Applies to</dt>
        <dd>{{ $rule->applies_to }}</dd>
        <dt>Reason</dt>
        <dd>{{ $rule->reason }}</dd>
        <dt>Status</dt>
        <dd>{{ $rule->is_active ? 'Active' : 'Inactive' }}</dd>
    </dl>
</section>
@endsection
