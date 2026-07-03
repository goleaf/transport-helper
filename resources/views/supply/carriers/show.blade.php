@extends('layouts.app')

@section('title')
Carrier
@endsection

@section('content')
<header>
    <h1>{{ $carrier->name }}</h1>
    <a href="{{ route('supply.carriers.edit', $carrier) }}">Edit</a>
</header>

<section>
    <p>Code: {{ $carrier->code }}</p>
    <p>Currency: {{ $carrier->default_currency }}</p>
    <p>Reliability: {{ $carrier->reliability_score }}</p>
    <p>Status: {{ $carrier->is_active ? 'Active' : 'Inactive' }}</p>
    <p>{{ $carrier->notes }}</p>
</section>

<section>
    <h2>Recent quotes</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier order</th>
                <th>Price</th>
                <th>Delivery</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($carrier->quotes as $quote)
                <tr>
                    <td><x-supply.table-action :href="route('supply.transport.quotes.show', $quote)" :label="'Quote #'.$quote->id" /></td>
                    <td>{{ $quote->supplier_order_id }}</td>
                    <td>{{ $quote->price }} {{ $quote->currency }}</td>
                    <td>{{ $quote->delivery_date?->toDateString() }}</td>
                    <td><x-supply.status-badge :status="$quote->status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No quotes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
