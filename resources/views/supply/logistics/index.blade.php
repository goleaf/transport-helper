@extends('layouts.app')

@section('title')
Logistics Records
@endsection

@section('content')
<header>
    <h1>Logistics Records</h1>
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
    @include('supply.logistics.partials.summary-cards', ['summary' => $summary])
</section>

<section>
    <h2>Filters</h2>
    <form method="get" action="{{ route('supply.logistics.index') }}">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            @forelse ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>
            @empty
                <option value="" disabled>No statuses.</option>
            @endforelse
        </select>

        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id">
            <option value="">All suppliers</option>
            @forelse ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @empty
                <option value="" disabled>No suppliers.</option>
            @endforelse
        </select>

        <label for="carrier_id">Carrier</label>
        <select id="carrier_id" name="carrier_id">
            <option value="">All carriers</option>
            @forelse ($carriers as $carrier)
                <option value="{{ $carrier->id }}" @selected((string) ($filters['carrier_id'] ?? '') === (string) $carrier->id)>{{ $carrier->name }}</option>
            @empty
                <option value="" disabled>No carriers.</option>
            @endforelse
        </select>

        <label>
            <input type="checkbox" name="delayed_only" value="1" @checked((bool) ($filters['delayed_only'] ?? false))>
            Delayed only
        </label>

        <label>
            <input type="checkbox" name="needs_review" value="1" @checked((bool) ($filters['needs_review'] ?? false))>
            Needs review
        </label>

        <button type="submit">Filter</button>
    </form>
</section>

<section>
    <h2>Export and Sync</h2>
    <form method="post" action="{{ route('supply.logistics.export') }}">
        @csrf
        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif
        <button type="submit">Export CSV</button>
    </form>

    <form method="post" action="{{ route('supply.logistics.sync.google-sheets') }}">
        @csrf
        <button type="submit">Sync Google Sheets</button>
    </form>
</section>

<section>
    <h2>Records</h2>
    <table>
        <thead>
            <tr>
                <th>Supplier order</th>
                <th>Supplier</th>
                <th>Carrier</th>
                <th>Order date</th>
                <th>Confirmation date</th>
                <th>Ready date</th>
                <th>Pickup date</th>
                <th>Delivery date</th>
                <th>Actual received</th>
                <th>Transport price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record->supplierOrder?->order_number }}</td>
                    <td>{{ $record->supplier?->name }}</td>
                    <td>{{ $record->carrier?->name }}</td>
                    <td>{{ $record->order_date?->toDateString() }}</td>
                    <td>{{ $record->confirmation_date?->toDateString() }}</td>
                    <td>{{ $record->ready_date?->toDateString() }}</td>
                    <td>{{ $record->pickup_date?->toDateString() }}</td>
                    <td>{{ $record->delivery_date?->toDateString() }}</td>
                    <td>{{ $record->actual_received_date?->toDateString() }}</td>
                    <td>{{ $record->transport_price }} {{ $record->currency }}</td>
                    <td>@include('supply.logistics.partials.status-badge', ['status' => $record->status])</td>
                    <td class="table-actions">
                        <x-supply.table-action :href="route('supply.logistics.show', $record)" label="Open" />
                        <x-supply.table-action :href="route('supply.logistics.edit', $record)" label="Edit" />
                        <x-supply.table-action :href="route('supply.logistics.receive.create', $record)" label="Receive" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">No logistics records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $records->links() }}
</section>
@endsection
