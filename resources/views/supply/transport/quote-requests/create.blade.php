@extends('layouts.app')

@section('title')
Prepare Carrier Quote Requests
@endsection

@section('content')
<header>
    <h1>Prepare Carrier Quote Requests</h1>
    <p>{{ $supplierOrder->order_number }} {{ $supplierOrder->supplier?->name }}</p>
</header>

<form method="post" action="{{ route('supply.transport.orders.quote-requests.store', $supplierOrder) }}">
    @csrf
    <fieldset>
        <legend>Carriers</legend>
        @foreach ($carriers as $carrier)
            <label><input type="checkbox" name="carrier_ids[]" value="{{ $carrier->id }}"> {{ $carrier->name }}</label>
        @endforeach
    </fieldset>
    <label>Pickup location <input name="pickup_location"></label>
    <label>Delivery location <input name="delivery_location"></label>
    <label>Ready date <input type="date" name="ready_date"></label>
    <label>Requested pickup date <input type="date" name="requested_pickup_date"></label>
    <label>Requested delivery date <input type="date" name="requested_delivery_date"></label>
    <label>Cargo description <textarea name="cargo_description"></textarea></label>
    <label>Pallet count <input name="pallet_count" inputmode="numeric"></label>
    <label>Weight <input name="weight" inputmode="decimal"></label>
    <label>Language <input name="language" value="en"></label>
    <label><input type="checkbox" name="create_email_drafts" value="1" checked> Create email drafts</label>
    <button type="submit">Prepare drafts</button>
</form>
@endsection
