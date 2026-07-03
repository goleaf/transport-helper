@extends('layouts.app')

@section('title')
Edit Logistics Record
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.logistics.show', $record) }}">Back to logistics record</a></p>
    <h1>Edit Logistics Record {{ $record->id }}</h1>
</header>

@if ($errors->any())
    <section>
        <h2>Errors</h2>
        <ul>
            @forelse ($errors->all() as $error)
                <li>{{ $error }}</li>
            @empty
                <li>No errors.</li>
            @endforelse
        </ul>
    </section>
@endif

<form method="post" action="{{ route('supply.logistics.update', $record) }}">
    @csrf
    @method('PATCH')

    <label>Order date <input type="date" name="order_date" value="{{ old('order_date', $record->order_date?->toDateString()) }}"></label>
    <label>Confirmation date <input type="date" name="confirmation_date" value="{{ old('confirmation_date', $record->confirmation_date?->toDateString()) }}"></label>
    <label>Ready date <input type="date" name="ready_date" value="{{ old('ready_date', $record->ready_date?->toDateString()) }}"></label>
    <label>Pickup date <input type="date" name="pickup_date" value="{{ old('pickup_date', $record->pickup_date?->toDateString()) }}"></label>
    <label>Delivery date <input type="date" name="delivery_date" value="{{ old('delivery_date', $record->delivery_date?->toDateString()) }}"></label>
    <label>Actual received date <input type="date" name="actual_received_date" value="{{ old('actual_received_date', $record->actual_received_date?->toDateString()) }}"></label>

    <label>Carrier
        <select name="carrier_id">
            <option value="">No carrier</option>
            @forelse ($carriers as $carrier)
                <option value="{{ $carrier->id }}" @selected((int) old('carrier_id', $record->carrier_id) === $carrier->id)>{{ $carrier->name }}</option>
            @empty
                <option value="" disabled>No carriers.</option>
            @endforelse
        </select>
    </label>

    <label>Transport price <input type="number" step="0.001" name="transport_price" value="{{ old('transport_price', $record->transport_price) }}"></label>
    <label>Currency <input type="text" name="currency" value="{{ old('currency', $record->currency) }}"></label>
    <label>Status
        <select name="status">
            @forelse ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $record->status?->value ?? $record->status) === $status->value)>{{ $status->value }}</option>
            @empty
                <option value="" disabled>No statuses.</option>
            @endforelse
        </select>
    </label>
    <label>Notes <textarea name="notes">{{ old('notes', $record->notes) }}</textarea></label>
    <label>Reason <textarea name="reason" required>{{ old('reason') }}</textarea></label>
    <label><input type="checkbox" name="override_date_conflicts" value="1" @checked(old('override_date_conflicts'))> Override date conflicts</label>

    <button type="submit">Save logistics update</button>
</form>
@endsection
