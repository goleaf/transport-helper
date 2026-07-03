@extends('layouts.app')

@section('title')
Manual Carrier Quote
@endsection

@section('content')
<header>
    <h1>Manual Carrier Quote</h1>
    <p>{{ $supplierOrder->order_number }} {{ $supplierOrder->supplier?->name }}</p>
</header>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="post" action="{{ route('supply.transport.orders.quotes.store', $supplierOrder) }}">
    @csrf
    <input type="hidden" name="supplier_order_id" value="{{ $supplierOrder->id }}">
    <label>Carrier
        <select class="select select-bordered select-primary" name="carrier_id">
            <option value="">Carrier name fallback</option>
            @foreach ($carriers as $carrier)
                <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
            @endforeach
        </select>
    </label>
    <label>Carrier name <input class="input input-bordered input-primary" name="carrier_name" value="{{ old('carrier_name') }}"></label>
    <label>Price <input class="input input-bordered input-primary" name="price" inputmode="decimal" value="{{ old('price') }}"></label>
    <label>Currency <input class="input input-bordered input-primary" name="currency" value="{{ old('currency', 'EUR') }}"></label>
    <label>Pickup date <input class="input input-bordered input-primary" type="date" name="pickup_date" value="{{ old('pickup_date') }}"></label>
    <label>Delivery date <input class="input input-bordered input-primary" type="date" name="delivery_date" value="{{ old('delivery_date') }}"></label>
    <label>Transit days <input class="input input-bordered input-primary" name="transit_days" inputmode="numeric" value="{{ old('transit_days') }}"></label>
    <label>Conditions <textarea class="textarea textarea-bordered textarea-primary" name="conditions">{{ old('conditions') }}</textarea></label>
    <label>Reliability score <input class="input input-bordered input-primary" name="reliability_score" inputmode="decimal" value="{{ old('reliability_score') }}"></label>
    <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_missing_delivery_date" value="1"> Allow missing delivery date</label>
    <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_zero_price" value="1"> Allow zero price</label>
    <x-supply.button type="submit">Save quote</x-supply.button>
</form>
@endsection
