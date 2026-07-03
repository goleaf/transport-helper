@extends('layouts.app')

@section('title')
Replenishment Profile
@endsection

@section('content')
<x-supply.page-header :title="$profile->name" subtitle="Replenishment profile" :status="$profile->status" :back-url="route('supply.forecasting.profiles.index')">
    <x-slot:actions>
        <x-supply.button :href="route('supply.forecasting.profiles.edit', $profile)" mode="outline">Edit</x-supply.button>
    </x-slot:actions>
</x-supply.page-header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $profile->company?->name }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $profile->supplier?->name ?? 'Any supplier' }}</dd>
        <dt>Product</dt>
        <dd>{{ $profile->product?->sku ?? 'Any product' }}</dd>
        <dt>Category</dt>
        <dd>{{ $profile->category ?? 'Any category' }}</dd>
        <dt>Priority</dt>
        <dd>{{ $profile->priority }}</dd>
        <dt>Lead time override</dt>
        <dd>{{ $profile->lead_time_days_override ?? 'Not set' }}</dd>
        <dt>Safety days override</dt>
        <dd>{{ $profile->safety_days_override ?? 'Not set' }}</dd>
        <dt>Safety stock multiplier</dt>
        <dd>{{ $profile->safety_stock_multiplier ?? '1.0000' }}</dd>
        <dt>Seasonality</dt>
        <dd>{{ $profile->seasonality_enabled ? $profile->seasonality_mode : 'Disabled' }}</dd>
        <dt>Promotion sales</dt>
        <dd>{{ $profile->exclude_promotions ? 'Excluded from refined inputs' : 'Included' }}</dd>
        <dt>Anomaly sales</dt>
        <dd>{{ $profile->exclude_anomalies ? 'Excluded from refined inputs' : 'Included' }}</dd>
        <dt>Outliers</dt>
        <dd>{{ $profile->outlier_detection_enabled ? 'Detected with multiplier '.$profile->outlier_multiplier : 'Not detected' }}</dd>
        <dt>Notes</dt>
        <dd>{{ $profile->notes ?? 'No notes' }}</dd>
    </dl>
</section>

<section>
    <form method="POST" action="{{ route('supply.forecasting.profiles.archive', $profile) }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="reason" value="Archived from profile page">
        <x-supply.button type="submit" mode="outline">Archive profile</x-supply.button>
    </form>
</section>
@endsection
