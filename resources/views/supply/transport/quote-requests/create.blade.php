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
            <label><input class="checkbox checkbox-primary" type="checkbox" name="carrier_ids[]" value="{{ $carrier->id }}"> {{ $carrier->name }}</label>
        @endforeach
    </fieldset>
    <label>Pickup location <input class="input input-bordered input-primary" name="pickup_location"></label>
    <label>Delivery location <input class="input input-bordered input-primary" name="delivery_location"></label>
    <label>Ready date <input class="input input-bordered input-primary" type="date" name="ready_date"></label>
    <label>Requested pickup date <input class="input input-bordered input-primary" type="date" name="requested_pickup_date"></label>
    <label>Requested delivery date <input class="input input-bordered input-primary" type="date" name="requested_delivery_date"></label>
    <label>Cargo description <textarea class="textarea textarea-bordered textarea-primary" name="cargo_description"></textarea></label>
    <label>Pallet count <input class="input input-bordered input-primary" name="pallet_count" inputmode="numeric"></label>
    <label>Weight <input class="input input-bordered input-primary" name="weight" inputmode="decimal"></label>
    <label>Language <input class="input input-bordered input-primary" name="language" value="en"></label>
    <label><input class="checkbox checkbox-primary" type="checkbox" name="create_email_drafts" value="1" checked> Create email drafts</label>
    <x-supply.button type="submit">Prepare drafts</x-supply.button>
</form>
@endsection
