@extends('layouts.app')

@section('title')
Supplier Order {{ $order->order_number }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Supplier order workflow</p>
        <h1>Supplier Order {{ $order->order_number }}</h1>
    </div>
    <div class="actions">
        @include('supply.supplier-orders.partials.status-badge', ['status' => $order->status])
        <x-supply.button :href="route('supply.supplier-orders.index')" mode="outline" variant="neutral">Back to orders</x-supply.button>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@if ($errors->any())
    <x-supply.alert tone="warning">
        <strong>Errors</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-supply.alert>
@endif

<section class="grid" aria-label="Supplier order summary">
    <div class="stat metric"><span class="stat-title">Items</span><strong class="stat-value">{{ $itemsCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Ordered quantity</span><strong class="stat-value">{{ number_format((float) $totalOrderedQuantity, 3) }}</strong></div>
    <div class="stat metric"><span class="stat-title">Confirmations</span><strong class="stat-value">{{ $confirmationsCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Inbound orders</span><strong class="stat-value">{{ $inboundOrdersCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Carrier quotes</span><strong class="stat-value">{{ $carrierQuotesCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Logistics records</span><strong class="stat-value">{{ $logisticsRecordsCount }}</strong></div>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Order header</p>
            <h2>{{ $order->supplier?->name }}</h2>
        </div>
    </div>

    <dl>
        <dt>Company</dt>
        <dd>{{ $order->company?->name }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $order->supplier?->name }}</dd>
        <dt>Supplier code</dt>
        <dd>{{ $order->supplier?->code ?? 'Not set' }}</dd>
        <dt>Order date</dt>
        <dd>{{ $order->order_date?->toDateString() ?? 'Not set' }}</dd>
        <dt>Email subject</dt>
        <dd>{{ $order->email_subject ?? 'No subject prepared' }}</dd>
        <dt>Notes</dt>
        <dd>{{ $order->notes ?? 'No notes' }}</dd>
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
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Proposal source</p>
            <h2>Approved planning input</h2>
        </div>
        @if ($order->orderProposal)
            <x-supply.button :href="route('supply.proposals.show', $order->orderProposal)" size="sm" mode="outline" variant="neutral">Open proposal</x-supply.button>
        @endif
    </div>

    @if ($order->orderProposal)
        <dl>
            <dt>Proposal</dt>
            <dd>Proposal #{{ $order->orderProposal->id }}</dd>
            <dt>Proposal status</dt>
            <dd><x-supply.status-badge :status="$order->orderProposal->status" /></dd>
            <dt>Total lines</dt>
            <dd>{{ $order->orderProposal->total_lines }}</dd>
            <dt>Approved at</dt>
            <dd>{{ $order->orderProposal->approved_at?->toDateTimeString() ?? 'Not approved' }}</dd>
            <dt>Calculation date</dt>
            <dd>{{ $order->orderProposal->calculationRun?->calculation_date?->toDateString() ?? 'Not set' }}</dd>
            <dt>Formula version</dt>
            <dd>{{ $order->orderProposal->calculationRun?->formula_version ?? 'Not linked' }}</dd>
            <dt>Proposal notes</dt>
            <dd>{{ $order->orderProposal->notes ?? 'No notes' }}</dd>
        </dl>
    @else
        <x-supply.empty-state title="No proposal link">This supplier order is not linked to an order proposal.</x-supply.empty-state>
    @endif
</section>

@include('supply.supplier-orders.partials.items-table', ['order' => $order])

@include('supply.procurement.partials.gate-panel', [
    'subjectType' => 'supplier_order',
    'subjectId' => $order->id,
    'actions' => [
        [
            'value' => 'approve_supplier_email',
            'label' => 'Approve supplier email',
            'description' => 'Check procurement policy before approving the supplier email.',
        ],
        [
            'value' => 'send_supplier_email',
            'label' => 'Send supplier email',
            'description' => 'Check procurement policy before sending supplier communication.',
        ],
    ],
])

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Inbound receiving</p>
            <h2>Linked inbound orders</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Inbound order</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Ordered at</th>
                <th>Ready date</th>
                <th>Expected arrival</th>
                <th>Confirmed arrival</th>
                <th>Items</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->inboundOrders as $inboundOrder)
                <tr>
                    <td>
                        <strong>{{ $inboundOrder->order_number }}</strong>
                        <span>Inbound #{{ $inboundOrder->id }}</span>
                    </td>
                    <td>{{ $inboundOrder->supplier_order_reference ?? 'Not provided' }}</td>
                    <td><x-supply.status-badge :status="$inboundOrder->status" /></td>
                    <td>{{ $inboundOrder->ordered_at?->toDateTimeString() ?? 'Not set' }}</td>
                    <td>{{ $inboundOrder->ready_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $inboundOrder->expected_arrival_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $inboundOrder->confirmed_arrival_date?->toDateString() ?? 'Not confirmed' }}</td>
                    <td>
                        @forelse ($inboundOrder->items as $item)
                            <span>{{ $item->product?->sku }}: {{ number_format((float) $item->ordered_quantity, 3) }}</span>
                        @empty
                            <span>No items</span>
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No inbound orders are linked yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Supplier response</p>
            <h2>Confirmations</h2>
        </div>
        @if ($canCreateManualConfirmation)
            <x-supply.button :href="route('supply.supplier-orders.confirmations.create', $order)" size="sm" mode="outline" variant="neutral">Create manual confirmation</x-supply.button>
        @endif
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Status</th>
                <th>Supplier reference</th>
                <th>Confirmation date</th>
                <th>Ready date</th>
                <th>Expected arrival</th>
                <th>Discrepancy</th>
                <th>Lines</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->confirmations as $confirmation)
                <tr>
                    <td><x-supply.status-badge :status="$confirmation->status" /></td>
                    <td>{{ $confirmation->supplier_reference ?? 'Not provided' }}</td>
                    <td>{{ $confirmation->confirmation_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $confirmation->ready_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $confirmation->expected_arrival_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $confirmation->discrepancy_summary ?? 'No discrepancy' }}</td>
                    <td>
                        @forelse ($confirmation->items as $item)
                            <span>{{ $item->product?->sku }}: {{ number_format((float) $item->confirmed_quantity, 3) }}</span>
                        @empty
                            <span>No lines</span>
                        @endforelse
                    </td>
                    <td><x-supply.table-action :href="route('supply.supplier-confirmations.show', $confirmation)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No supplier confirmations.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<x-supply.supplier-order-transport-summary :order="$order" :can-manage-transport="$canManageTransport" />

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Logistics execution</p>
            <h2>Movement records</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Status</th>
                <th>Carrier</th>
                <th>Ready</th>
                <th>Pickup</th>
                <th>Delivery</th>
                <th>Received</th>
                <th>Transport price</th>
                <th>Delay reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->logisticsRecords as $record)
                <tr>
                    <td>@include('supply.logistics.partials.status-badge', ['status' => $record->status])</td>
                    <td>{{ $record->carrier?->name ?? 'Not selected' }}</td>
                    <td>{{ $record->ready_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $record->pickup_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $record->delivery_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $record->actual_received_date?->toDateString() ?? 'Not received' }}</td>
                    <td>{{ $record->transport_price ?? 'Not set' }} {{ $record->currency }}</td>
                    <td>{{ $record->delay_reason ?? 'No delay' }}</td>
                    <td>
                        <div class="table-actions">
                            <x-supply.table-action :href="route('supply.logistics.show', $record)" label="Open logistics record" />
                            <x-supply.table-action :href="route('supply.logistics.receive.create', $record)" label="Receive" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No logistics record is linked yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

@include('supply.supplier-orders.partials.export-panel', [
    'order' => $order,
    'exportFiles' => $exportFiles,
    'canExport' => $canExport,
    'exportFormats' => $exportFormats,
    'exportTypeLabels' => $exportTypeLabels,
])
@include('supply.supplier-orders.partials.email-panel', [
    'order' => $order,
    'emailMessage' => $emailMessage,
    'canPrepareEmail' => $canPrepareEmail,
    'canApproveEmail' => $canApproveEmail,
    'canSendEmail' => $canSendEmail,
])
@include('supply.supplier-orders.partials.audit-history', ['auditLogs' => $auditLogs])
@endsection
