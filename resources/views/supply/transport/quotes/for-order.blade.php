@extends('layouts.app')

@section('title')
Carrier Quotes For Supplier Order
@endsection

@section('content')
<header>
    <h1>Carrier Quotes</h1>
    <p>Supplier order {{ $supplierOrder->order_number }} {{ $supplierOrder->supplier?->name }}</p>
    <a href="{{ route('supply.transport.orders.quotes.create', $supplierOrder) }}">Add manual quote</a>
    <a href="{{ route('supply.transport.orders.quote-requests.create', $supplierOrder) }}">Prepare quote requests</a>
    <a href="{{ route('supply.supplier-orders.show', $supplierOrder) }}">Back to supplier order</a>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<section>
    <p>System recommendation is not automatic carrier selection. User must select carrier.</p>
    <form method="post" action="{{ route('supply.transport.orders.quotes.score', $supplierOrder) }}">
        @csrf
        <label>Required pickup date <input type="date" name="required_pickup_date"></label>
        <label>Required delivery date <input type="date" name="required_delivery_date"></label>
        <button type="submit">Re-score quotes</button>
    </form>
</section>

<section>
    @include('supply.transport.partials.comparison-table', ['quotes' => $quotes])
    {{ $quotes->links() }}
</section>
@endsection
