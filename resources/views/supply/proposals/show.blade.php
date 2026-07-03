@extends('layouts.app')

@section('title')
Order Proposal #{{ $proposal->id }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Human approval queue</p>
        <h1>Order Proposal #{{ $proposal->id }}</h1>
    </div>
    <x-supply.button :href="route('supply.proposals.index')" mode="outline" variant="neutral">Back to proposals</x-supply.button>
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

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Proposal header</p>
            <h2>{{ $proposal->supplier?->name }}</h2>
        </div>
    </div>

    <dl>
        <dt>Supplier</dt>
        <dd>{{ $proposal->supplier?->name }}</dd>
        <dt>Company</dt>
        <dd>{{ $proposal->company?->name }}</dd>
        <dt>Calculation date</dt>
        <dd>{{ $proposal->calculationRun?->calculation_date?->toDateString() }}</dd>
        <dt>Formula version</dt>
        <dd>{{ $proposal->calculationRun?->formula_version }}</dd>
        <dt>Status</dt>
        <dd>@include('supply.proposals.partials.status-badge', ['status' => $proposal->status])</dd>
        <dt>Created by</dt>
        <dd>{{ $proposal->createdBy?->name ?? 'System' }}</dd>
        <dt>Approved by</dt>
        <dd>{{ $proposal->approvedBy?->name ?? 'Not approved' }}</dd>
        <dt>Approved at</dt>
        <dd>{{ $proposal->approved_at?->toDateTimeString() ?? 'Not approved' }}</dd>
        <dt>Notes</dt>
        <dd>{{ $proposal->notes ?? 'No notes' }}</dd>
    </dl>
</section>

<section class="grid" aria-label="Proposal summary">
    <div class="stat metric"><span class="stat-title">Total lines</span><strong class="stat-value">{{ $summary['total_lines'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Unresolved lines</span><strong class="stat-value">{{ $summary['unresolved_count'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Needs review</span><strong class="stat-value">{{ $summary['needs_review_count'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Approved</span><strong class="stat-value">{{ $summary['approved_count'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Adjusted</span><strong class="stat-value">{{ $summary['adjusted_count'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Rejected</span><strong class="stat-value">{{ $summary['rejected_count'] }}</strong></div>
    <div class="stat metric"><span class="stat-title">Total recommended quantity</span><strong class="stat-value">{{ number_format((float) $summary['total_recommended_quantity'], 3) }}</strong></div>
    <div class="stat metric"><span class="stat-title">Total approved quantity</span><strong class="stat-value">{{ number_format((float) $summary['total_approved_quantity'], 3) }}</strong></div>
</section>

@if ($summary['unresolved_count'] > 0)
    <x-supply.alert tone="warning">This proposal has unresolved items. Resolve draft and needs review lines before proposal approval.</x-supply.alert>
@endif

@if ($summary['orderable_count'] === 0)
    <x-supply.alert tone="warning">This proposal has no approved or adjusted positive-quantity lines.</x-supply.alert>
@endif

@if ($proposal->supplierOrder)
    <x-supply.alert tone="info">Converted supplier order: {{ $proposal->supplierOrder->order_number }}</x-supply.alert>
@endif

<section>
    <h2>Actions</h2>
    <div class="actions">
        @if ($canApproveProposal)
            <form method="post" action="{{ route('supply.proposals.approve', $proposal) }}">
                @csrf
                <input type="hidden" name="confirmation" value="1">
                <x-supply.button type="submit" :disabled="! $summary['can_approve']">Approve whole proposal</x-supply.button>
            </form>
        @endif

        @if ($canConvertProposal)
            <form method="post" action="{{ route('supply.proposals.convert-to-supplier-order', $proposal) }}">
                @csrf
                <input type="hidden" name="confirmation" value="1">
                <x-supply.button type="submit" :disabled="! $summary['can_convert']">Convert to supplier order</x-supply.button>
            </form>
        @endif

        <x-supply.button :href="route('supply.proposals.index')" mode="outline" variant="neutral">Back to proposals</x-supply.button>
    </div>

    @if ($summary['blocking_reasons'])
        <p>Blocking reasons: <x-supply.inline-list :items="$summary['blocking_reasons']" /></p>
    @endif
</section>

<section>
    <h2>Status filters</h2>
    <div class="actions">
        <x-supply.button :href="route('supply.proposals.show', $proposal)" size="sm" mode="outline" variant="neutral">All</x-supply.button>
        @foreach ($statuses as $status)
            <x-supply.button :href="route('supply.proposals.show', ['proposal' => $proposal, 'status' => $status->value])" size="sm" mode="outline" variant="neutral">
                <x-supply.human-label :value="$status" />
            </x-supply.button>
        @endforeach
    </div>
    @if ($statusFilter)
        <p>Current filter: {{ $statusFilter }}</p>
    @endif
</section>

<section>
    <h2>Items</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product name</th>
                <th>Status</th>
                <th>Human review</th>
                <th>T0</th>
                <th>T1</th>
                <th>T2</th>
                <th>T3</th>
                <th>Trend</th>
                <th>Need T0-T1</th>
                <th>Stock T1</th>
                <th>Need T1-T2</th>
                <th>Safety stock</th>
                <th>Raw need</th>
                <th>Recommended quantity</th>
                <th>Approved quantity</th>
                <th>Warnings</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>@include('supply.proposals.partials.status-badge', ['status' => $item->status])</td>
                    <td>{{ $item->requires_human_review ? 'Yes' : 'No' }}</td>
                    <td>{{ $item->t0_date?->toDateString() }}</td>
                    <td>{{ $item->t1_date?->toDateString() }}</td>
                    <td>{{ $item->t2_date?->toDateString() }}</td>
                    <td>{{ $item->t3_date?->toDateString() }}</td>
                    <td>{{ $item->trend }}</td>
                    <td>{{ $item->need_t0_t1 }}</td>
                    <td>{{ $item->stock_t1 }}</td>
                    <td>{{ $item->need_t1_t2 }}</td>
                    <td>{{ $item->safety_stock }}</td>
                    <td>{{ $item->raw_need }}</td>
                    <td>{{ $item->recommended_quantity }}</td>
                    <td>{{ $item->approved_quantity }}</td>
                    <td><x-supply.proposal-warnings :warnings="$item->warnings_json" /></td>
                    <td><x-supply.table-action :href="route('supply.proposals.items.show', [$proposal, $item])" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="18">No proposal items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Supplier coverage</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Product</th>
                <th>Supplier</th>
                <th>Supplier SKU</th>
                <th>MOQ</th>
                <th>Pack</th>
                <th>Pallet</th>
                <th>Lead time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @forelse ($item->product?->supplierProductRules ?? [] as $rule)
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td>{{ $rule->supplier?->name }}</td>
                        <td>{{ $rule->supplier_sku }}</td>
                        <td>{{ $rule->moq }}</td>
                        <td>{{ $rule->pack_multiple }}</td>
                        <td>{{ $rule->pallet_quantity }}</td>
                        <td>{{ $rule->lead_time_days }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td colspan="6">No supplier rules.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="7">No proposal items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Stock position</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Product</th>
                <th>Date</th>
                <th>Free</th>
                <th>Total</th>
                <th>Reserved</th>
                <th>In transit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @forelse ($item->product?->stockSnapshots ?? [] as $snapshot)
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td>{{ $snapshot->snapshot_date?->toDateString() }}</td>
                        <td>{{ $snapshot->free_stock }}</td>
                        <td>{{ $snapshot->total_stock }}</td>
                        <td>{{ $snapshot->reserved_quantity }}</td>
                        <td>{{ $snapshot->in_transit_quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td colspan="5">No stock snapshots.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="6">No proposal items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Sales demand</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Product</th>
                <th>Date</th>
                <th>Quantity</th>
                <th>Channel</th>
                <th>Flags</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @forelse ($item->product?->salesHistory ?? [] as $row)
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td>{{ $row->sales_date?->toDateString() }}</td>
                        <td>{{ $row->quantity }}</td>
                        <td>{{ $row->channel ?? 'Not set' }}</td>
                        <td>
                            @if ($row->is_promotion)
                                <x-supply.badge tone="info">Promotion</x-supply.badge>
                            @endif
                            @if ($row->is_anomaly)
                                <x-supply.badge tone="warning">Anomaly</x-supply.badge>
                            @endif
                            @if (! $row->is_promotion && ! $row->is_anomaly)
                                <span>Normal</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td colspan="4">No sales rows.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="5">No proposal items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Reservations</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Product</th>
                <th>Project</th>
                <th>Customer</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Expected usage</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @forelse ($item->product?->reservations ?? [] as $reservation)
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td>{{ $reservation->project_name ?? 'Not set' }}</td>
                        <td>{{ $reservation->customer_name ?? 'Not set' }}</td>
                        <td>{{ $reservation->quantity }}</td>
                        <td>{{ $reservation->status }}</td>
                        <td>{{ $reservation->expected_usage_date?->toDateString() ?? 'Not set' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td colspan="5">No reservations.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="6">No proposal items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <h2>Inbound quantities</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Product</th>
                <th>Order</th>
                <th>Ordered</th>
                <th>Confirmed</th>
                <th>Received</th>
                <th>Expected arrival</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @forelse ($item->product?->inboundOrderItems ?? [] as $inboundItem)
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td>{{ $inboundItem->inboundOrder?->order_number }}</td>
                        <td>{{ $inboundItem->ordered_quantity }}</td>
                        <td>{{ $inboundItem->confirmed_quantity ?? 'Not confirmed' }}</td>
                        <td>{{ $inboundItem->received_quantity ?? 'Not received' }}</td>
                        <td>{{ $inboundItem->expected_arrival_date?->toDateString() ?? 'Not set' }}</td>
                        <td>{{ $inboundItem->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $item->product?->sku }}</td>
                        <td colspan="6">No inbound quantities.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="7">No proposal items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
