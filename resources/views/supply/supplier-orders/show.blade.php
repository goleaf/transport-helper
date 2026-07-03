@extends('layouts.app')

@section('title')
Supplier Order {{ $order->order_number }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.supplier-orders.index') }}">Back to supplier orders</a></p>
    <h1>Supplier Order {{ $order->order_number }}</h1>
    @include('supply.supplier-orders.partials.status-badge', ['status' => $order->status])
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
    <section>
        <h2>Errors</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif

<section>
    <dl>
        <dt>Supplier</dt>
        <dd>{{ $order->supplier?->name }}</dd>
        <dt>Order date</dt>
        <dd>{{ $order->order_date?->toDateString() }}</dd>
        <dt>Created from proposal</dt>
        <dd>
            @if ($order->orderProposal)
                <a href="{{ route('supply.proposals.show', $order->orderProposal) }}">Proposal #{{ $order->orderProposal->id }}</a>
            @else
                Not linked
            @endif
        </dd>
        <dt>Email approved by</dt>
        <dd>{{ $order->emailApprovedBy?->name ?? 'Not approved' }}</dd>
        <dt>Email approved at</dt>
        <dd>{{ $order->email_approved_at?->toDateTimeString() ?? 'Not approved' }}</dd>
        <dt>Sent by</dt>
        <dd>{{ $order->sentBy?->name ?? 'Not sent' }}</dd>
        <dt>Sent at</dt>
        <dd>{{ $order->sent_at?->toDateTimeString() ?? 'Not sent' }}</dd>
    </dl>
</section>

<section>
    <h2>Summary</h2>
    <dl>
        <dt>Items count</dt>
        <dd>{{ $itemsCount }}</dd>
        <dt>Total ordered quantity</dt>
        <dd>{{ $totalOrderedQuantity }}</dd>
        <dt>Latest export</dt>
        <dd>{{ $exportFiles->first()?->filename ?? 'No exports' }}</dd>
        <dt>Email status</dt>
        <dd><x-supply.status-badge :status="$emailMessage?->status ?? 'No draft'" /></dd>
        <dt>Logistics status</dt>
        <dd><x-supply.status-badge :status="$firstLogisticsRecord?->status ?? 'No logistics record'" /></dd>
    </dl>
</section>

        @include('supply.supplier-orders.partials.items-table', ['order' => $order])
        <section>
            <h2>Supplier confirmations</h2>
            @if ($canCreateManualConfirmation)
                <p><a href="{{ route('supply.supplier-orders.confirmations.create', $order) }}">Create manual confirmation</a></p>
            @endif
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Supplier reference</th>
                        <th>Confirmation date</th>
                        <th>Ready date</th>
                        <th>Expected arrival date</th>
                        <th>Discrepancy summary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->confirmations as $confirmation)
                        <tr>
                            <td>{{ $confirmation->status instanceof \BackedEnum ? $confirmation->status->value : $confirmation->status }}</td>
                            <td>{{ $confirmation->supplier_reference }}</td>
                            <td>{{ $confirmation->confirmation_date?->toDateString() }}</td>
                            <td>{{ $confirmation->ready_date?->toDateString() }}</td>
                            <td>{{ $confirmation->expected_arrival_date?->toDateString() }}</td>
                            <td>{{ $confirmation->discrepancy_summary }}</td>
                            <td><a href="{{ route('supply.supplier-confirmations.show', $confirmation) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No supplier confirmations.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section>
            <h2>Transport</h2>
            @php
                $selectedLogisticsRecord = $order->logisticsRecords->first(fn ($record) => $record->selected_carrier_quote_id !== null) ?? $order->logisticsRecords->first();
                $selectedQuote = $selectedLogisticsRecord?->selectedCarrierQuote ?? $order->carrierQuotes->first(fn ($quote) => ($quote->status instanceof \BackedEnum ? $quote->status->value : $quote->status) === 'selected');
            @endphp
            <dl>
                <dt>Quote count</dt>
                <dd>{{ $order->carrierQuotes->count() }}</dd>
                <dt>Selected carrier</dt>
                <dd>{{ $selectedQuote?->carrier?->name ?? $selectedLogisticsRecord?->carrier?->name ?? 'Not selected' }}</dd>
                <dt>Selected quote price</dt>
                <dd>{{ $selectedQuote?->price }} {{ $selectedQuote?->currency }}</dd>
                <dt>Pickup date</dt>
                <dd>{{ $selectedLogisticsRecord?->pickup_date?->toDateString() ?? $selectedQuote?->pickup_date?->toDateString() }}</dd>
                <dt>Delivery date</dt>
                <dd>{{ $selectedLogisticsRecord?->delivery_date?->toDateString() ?? $selectedQuote?->delivery_date?->toDateString() }}</dd>
            </dl>
            <p><a href="{{ route('supply.transport.orders.quotes', $order) }}">Compare carrier quotes</a></p>
            @if ($canManageTransport)
                <p><a href="{{ route('supply.transport.orders.quote-requests.create', $order) }}">Prepare quote requests</a></p>
                <p><a href="{{ route('supply.transport.orders.quotes.create', $order) }}">Add manual quote</a></p>
            @endif
        </section>
        @include('supply.supplier-orders.partials.export-panel', ['order' => $order, 'exportFiles' => $exportFiles, 'canExport' => $canExport])
@include('supply.supplier-orders.partials.email-panel', [
    'order' => $order,
    'emailMessage' => $emailMessage,
    'canPrepareEmail' => $canPrepareEmail,
    'canApproveEmail' => $canApproveEmail,
    'canSendEmail' => $canSendEmail,
])
@include('supply.supplier-orders.partials.audit-history', ['auditLogs' => $auditLogs])
@endsection
