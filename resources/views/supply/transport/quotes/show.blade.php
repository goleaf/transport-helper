@extends('layouts.app')

@section('title')
Carrier Quote
@endsection

@section('content')
<header>
    <h1>Carrier Quote #{{ $quote->id }}</h1>
    <a href="{{ route('supply.transport.orders.quotes', $quote->supplierOrder) }}">Quotes for order</a>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<section>
    <p>Carrier: {{ $quote->carrier?->name }}</p>
    <p>Supplier order: {{ $quote->supplierOrder?->order_number }}</p>
    <p>Source: {{ $quote->source_type }} {{ $quote->source_id }}</p>
    <p>Price: {{ $quote->price }} {{ $quote->currency }}</p>
    <p>Pickup date: {{ $quote->pickup_date?->toDateString() }}</p>
    <p>Delivery date: {{ $quote->delivery_date?->toDateString() }}</p>
    <p>Transit days: {{ $quote->transit_days }}</p>
    <p>Conditions: {{ $quote->conditions }}</p>
    <p>Reliability score: {{ $quote->reliability_score }}</p>
    <p>Calculated score: {{ $quote->calculated_score }}</p>
    <p>Status: @include('supply.transport.partials.quote-status-badge', ['status' => $quote->status])</p>
    <p>Selected at: {{ $quote->selected_at?->toDateTimeString() }}</p>
</section>

<section>
    <h2>Linked Logistics</h2>
    @forelse ($quote->supplierOrder?->logisticsRecords ?? [] as $record)
        @if ((int) $record->selected_carrier_quote_id === (int) $quote->id)
            <p>
                <a href="{{ route('supply.logistics.show', $record) }}">Logistics record #{{ $record->id }}</a>
                @include('supply.logistics.partials.status-badge', ['status' => $record->status])
                Pickup {{ $record->pickup_date?->toDateString() ?? 'not set' }},
                delivery {{ $record->delivery_date?->toDateString() ?? 'not set' }}.
            </p>
        @endif
    @empty
        <p>No logistics record linked.</p>
    @endforelse
</section>

<section>
    <h2>Score explanation</h2>
    @include('supply.transport.partials.score-explanation', ['explanation' => $quote->score_explanation_json])
</section>

<section>
    <h2>Warnings</h2>
    <x-supply.structured-value :value="$quote->warnings_json" />
    <h2>Validation errors</h2>
    <x-supply.structured-value :value="$quote->validation_errors_json" />
</section>

<section>
    <h2>Actions</h2>
    @include('supply.transport.partials.quote-actions', ['quote' => $quote])
</section>
@endsection
