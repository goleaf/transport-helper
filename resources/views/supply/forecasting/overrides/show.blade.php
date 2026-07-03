@extends('layouts.app')

@section('title')
Trend Override
@endsection

@section('content')
<x-supply.page-header title="Trend Override" :subtitle="$override->reason" :status="$override->status" :back-url="route('supply.forecasting.overrides.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $override->company?->name }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $override->supplier?->name ?? 'Any supplier' }}</dd>
        <dt>Product</dt>
        <dd>{{ $override->product?->sku ?? 'Any product' }}</dd>
        <dt>Category</dt>
        <dd>{{ $override->category ?? 'Any category' }}</dd>
        <dt>Trend value</dt>
        <dd>{{ $override->trend_value }}</dd>
        <dt>Date range</dt>
        <dd>{{ $override->date_from?->toDateString() }} to {{ $override->date_to?->toDateString() }}</dd>
        <dt>Reason</dt>
        <dd>{{ $override->reason }}</dd>
        <dt>Approval note</dt>
        <dd>{{ $override->approval_note ?? 'Not approved' }}</dd>
        <dt>Rejection or revocation reason</dt>
        <dd>{{ $override->rejection_reason ?? 'Not rejected or revoked' }}</dd>
        <dt>Approved by</dt>
        <dd>{{ $override->approvedBy?->name ?? 'Not approved' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Human approval</p>
            <h2>Override actions</h2>
        </div>
    </div>

    <div class="button-row">
        <form method="POST" action="{{ route('supply.forecasting.overrides.submit', $override) }}">
            @csrf
            <x-supply.button type="submit" mode="outline">Submit for approval</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.forecasting.overrides.approve', $override) }}">
            @csrf
            <input class="input input-bordered" name="note" value="Approved for deterministic scenario use" required>
            <x-supply.button type="submit">Approve</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.forecasting.overrides.reject', $override) }}">
            @csrf
            <input class="input input-bordered" name="reason" value="Rejected after manual review" required>
            <x-supply.button type="submit" mode="outline">Reject</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.forecasting.overrides.revoke', $override) }}">
            @csrf
            <input class="input input-bordered" name="reason" value="Revoked after manual review" required>
            <x-supply.button type="submit" mode="outline">Revoke</x-supply.button>
        </form>
    </div>
</section>
@endsection
