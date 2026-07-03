@extends('layouts.app')

@section('title')
Carrier Quotes
@endsection

@section('content')
<header>
    <h1>Carrier Quotes</h1>
    @if ($supplierOrder)
        <p>Supplier order {{ $supplierOrder->order_number }} {{ $supplierOrder->supplier?->name }}</p>
    @endif
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

@if ($supplierOrder)
    <section>
        <h2>Request Quotes</h2>
        <form method="post" action="{{ route('supply.transport.orders.request-quotes', $supplierOrder) }}">
            @csrf
            <label for="required_pickup_date">Required pickup date</label>
            <input class="input input-bordered input-primary" id="required_pickup_date" name="required_pickup_date" type="date">

            <label for="required_delivery_date">Required delivery date</label>
            <input class="input input-bordered input-primary" id="required_delivery_date" name="required_delivery_date" type="date">

            <label for="message">Message</label>
            <textarea class="textarea textarea-bordered textarea-primary" id="message" name="message"></textarea>

            <x-supply.button type="submit">Request quotes</x-supply.button>
        </form>
    </section>

    <section>
        <h2>Manual Quote</h2>
        <form method="post" action="{{ route('supply.transport.quotes.manual') }}">
            @csrf
            <input type="hidden" name="supplier_order_id" value="{{ $supplierOrder->id }}">

            <label for="carrier_id">Carrier</label>
            <select class="select select-bordered select-primary" id="carrier_id" name="carrier_id">
                <option value="">New carrier</option>
                @foreach ($carriers as $carrier)
                    <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                @endforeach
            </select>

            <label for="carrier_name">Carrier name</label>
            <input class="input input-bordered input-primary" id="carrier_name" name="carrier_name">

            <label for="price">Price</label>
            <input class="input input-bordered input-primary" id="price" name="price" inputmode="decimal">

            <label for="currency">Currency</label>
            <input class="input input-bordered input-primary" id="currency" name="currency" value="EUR">

            <label for="pickup_date">Pickup date</label>
            <input class="input input-bordered input-primary" id="pickup_date" name="pickup_date" type="date">

            <label for="delivery_date">Delivery date</label>
            <input class="input input-bordered input-primary" id="delivery_date" name="delivery_date" type="date">

            <label for="transit_days">Transit days</label>
            <input class="input input-bordered input-primary" id="transit_days" name="transit_days" inputmode="numeric">

            <label for="conditions">Conditions</label>
            <textarea class="textarea textarea-bordered textarea-primary" id="conditions" name="conditions"></textarea>

            <x-supply.button type="submit">Save quote</x-supply.button>
        </form>
    </section>
@endif

<section>
    <h2>Quotes</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Supplier order</th>
                <th>Carrier</th>
                <th>Price</th>
                <th>Pickup date</th>
                <th>Delivery date</th>
                <th>Reliability score</th>
                <th>Calculated score</th>
                <th>Warnings</th>
                <th>Score explanation</th>
                <th>Status</th>
                <th>Audit history</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quotes as $quote)
                <tr>
                    <td>{{ $quote->supplierOrder?->order_number }}</td>
                    <td>{{ $quote->carrier?->name }}</td>
                    <td>{{ $quote->price }} {{ $quote->currency }}</td>
                    <td>{{ $quote->pickup_date?->toDateString() }}</td>
                    <td>{{ $quote->delivery_date?->toDateString() }}</td>
                    <td>{{ $quote->reliability_score }}</td>
                    <td>{{ $quote->calculated_score }}</td>
                    <x-supply.carrier-quote-score-cells :quote="$quote" />
                    <td><x-supply.status-badge :status="$quote->status" /></td>
                    <td>
                        <ul>
                            @forelse (($auditLogsByQuoteId[$quote->id] ?? collect()) as $auditLog)
                                <li>{{ $auditLog->created_at?->toDateTimeString() }} {{ $auditLog->event_type }} {{ $auditLog->user?->name }}</li>
                            @empty
                                <li>No audit logs.</li>
                            @endforelse
                        </ul>
                    </td>
                    <td>
                        <form method="post" action="{{ route('supply.transport.quotes.select', $quote) }}">
                            @csrf
                            <input type="hidden" name="confirm_selection" value="1">
                            <x-supply.button type="submit">Select</x-supply.button>
                        </form>
                        <form method="post" action="{{ route('supply.transport.quotes.reject', $quote) }}">
                            @csrf
                            <x-supply.button type="submit">Reject</x-supply.button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">No carrier quotes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $quotes->links() }}
</section>
@endsection
