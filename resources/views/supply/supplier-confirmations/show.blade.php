@extends('layouts.app')

@section('title')
Supplier Confirmation {{ $confirmation->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.supplier-confirmations.index') }}">Back to supplier confirmations</a></p>
    <h1>Supplier Confirmation {{ $confirmation->id }}</h1>
    @include('supply.supplier-confirmations.partials.status-badge', ['status' => $confirmation->status])
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<section>
    <dl>
        <dt>Supplier</dt>
        <dd>{{ $confirmation->supplierOrder?->supplier?->name }}</dd>
        <dt>Supplier order</dt>
        <dd><a href="{{ route('supply.supplier-orders.show', $confirmation->supplierOrder) }}">{{ $confirmation->supplierOrder?->order_number }}</a></dd>
        <dt>Supplier reference</dt>
        <dd>{{ $confirmation->supplier_reference }}</dd>
        <dt>Confirmation date</dt>
        <dd>{{ $confirmation->confirmation_date?->toDateString() }}</dd>
        <dt>Ready date</dt>
        <dd>{{ $confirmation->ready_date?->toDateString() }}</dd>
        <dt>Shipping date</dt>
        <dd>{{ $confirmation->shipping_date?->toDateString() }}</dd>
        <dt>Expected arrival date</dt>
        <dd>{{ $confirmation->expected_arrival_date?->toDateString() }}</dd>
        <dt>Applied by</dt>
        <dd>{{ $confirmation->appliedBy?->name }}</dd>
        <dt>Applied at</dt>
        <dd>{{ $confirmation->applied_at?->toDateTimeString() }}</dd>
    </dl>
</section>

@include('supply.supplier-confirmations.partials.source-panel', ['confirmation' => $confirmation])
@include('supply.supplier-confirmations.partials.discrepancies', ['confirmation' => $confirmation])
@include('supply.supplier-confirmations.partials.items-table', ['confirmation' => $confirmation])
@include('supply.supplier-confirmations.partials.audit-history', ['auditLogs' => $auditLogs])
@endsection
