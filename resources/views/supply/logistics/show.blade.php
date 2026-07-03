@extends('layouts.app')

@section('title')
Logistics Record {{ $record->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.logistics.index') }}">Back to logistics</a></p>
    <h1>Logistics Record {{ $record->id }}</h1>
    <p>
        <a href="{{ route('supply.logistics.edit', $record) }}">Edit logistics</a>
        <a href="{{ route('supply.logistics.receive.create', $record) }}">Record goods received</a>
    </p>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

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

<section>
    <h2>Details</h2>
    <dl>
        <dt>Company</dt>
        <dd>{{ $record->company?->name }}</dd>

        <dt>Supplier order</dt>
        <dd>{{ $record->supplierOrder?->order_number }}</dd>

        <dt>Supplier</dt>
        <dd>{{ $record->supplier?->name }}</dd>

        <dt>Carrier</dt>
        <dd>{{ $record->carrier?->name }}</dd>

        <dt>Order date</dt>
        <dd>{{ $record->order_date?->toDateString() }}</dd>

        <dt>Confirmation date</dt>
        <dd>{{ $record->confirmation_date?->toDateString() }}</dd>

        <dt>Ready date</dt>
        <dd>{{ $record->ready_date?->toDateString() }}</dd>

        <dt>Pickup date</dt>
        <dd>{{ $record->pickup_date?->toDateString() }}</dd>

        <dt>Delivery date</dt>
        <dd>{{ $record->delivery_date?->toDateString() }}</dd>

        <dt>Actual received date</dt>
        <dd>{{ $record->actual_received_date?->toDateString() }}</dd>

        <dt>Transport price</dt>
        <dd>{{ $record->transport_price }} {{ $record->currency }}</dd>

        <dt>Status</dt>
        <dd>@include('supply.logistics.partials.status-badge', ['status' => $record->status])</dd>

        <dt>External sheet reference</dt>
        <dd>{{ $record->external_sheet_reference }}</dd>

        <dt>Supplier confirmation</dt>
        <dd>{{ $record->supplierConfirmation?->supplier_reference ?? 'Not linked' }}</dd>

        <dt>Selected quote</dt>
        <dd>
            @if ($record->selectedCarrierQuote)
                {{ $record->selectedCarrierQuote->price }} {{ $record->selectedCarrierQuote->currency }}
            @else
                Not linked
            @endif
        </dd>

        <dt>Notes</dt>
        <dd>{{ $record->notes }}</dd>
    </dl>
</section>

<section>
    @include('supply.logistics.partials.timeline', ['record' => $record])
</section>

<section>
    @include('supply.logistics.partials.receiving-discrepancies', ['record' => $record])
</section>

<section>
    <h2>Update Status</h2>
    <form method="post" action="{{ route('supply.logistics.status.update', $record) }}">
        @csrf
        <label for="status">Status</label>
        <select id="status" name="status">
            @forelse ($statuses as $status)
                <option value="{{ $status->value }}" @selected($record->status_value === $status->value)>{{ $status->value }}</option>
            @empty
                <option value="" disabled>No statuses.</option>
            @endforelse
        </select>

        <label for="reason">Reason</label>
        <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>

        <button type="submit">Update status</button>
    </form>
</section>

<section>
    @include('supply.logistics.partials.audit-history', ['auditLogs' => $auditLogs])
</section>
@endsection
