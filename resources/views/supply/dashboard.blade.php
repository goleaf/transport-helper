@extends('layouts.app')

@section('title')
Supply Dashboard
@endsection

@section('content')
<x-supply.page-header
    title="Supply Dashboard"
    subtitle="Workflow-first operator view for replenishment, email review, transport, logistics and UAT."
/>

<section id="dashboard-kpis" aria-label="Dashboard KPI cards">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($kpis as $kpi)
            <x-supply.kpi-card
                :title="$kpi['title']"
                :value="$kpi['value']"
                :subtitle="$kpi['subtitle']"
                :tone="$kpi['tone']"
                :url="$kpi['url']"
            />
        @empty
            <x-supply.empty-state title="No KPI cards" message="Supply dashboard metrics will appear after the first workflow records exist." />
        @endforelse
    </div>
</section>

<section id="action-queue">
    <h2>My Action Queue</h2>
    @if ($hasActionQueue)
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($actionQueue as $item)
                <x-supply.action-card :item="$item" />
            @empty
                <x-supply.empty-state title="No urgent actions" message="No urgent actions are assigned to your workflow queue." />
            @endforelse
        </div>
    @else
        <x-supply.empty-state title="No urgent actions" message="No urgent actions are assigned to your workflow queue." />
    @endif
</section>

<section id="environment">
    <h2>Environment</h2>
    <div class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <x-supply.environment-badges />
            <p class="text-sm text-slate-600">Real integrations, external AI and real email are displayed explicitly so operators know when the system is in safe mode.</p>
        </div>
    </div>
</section>

<section id="today-supply-timeline">
    <h2>Today supply timeline</h2>
    @if ($hasTimelineItems)
        <ol class="timeline timeline-vertical">
            @forelse ($timelineItems as $item)
                <li>{{ $item['label'] }}: {{ $item['value'] }}</li>
            @empty
                <li>No timeline activity yet.</li>
            @endforelse
        </ol>
    @else
        <x-supply.empty-state title="No timeline activity yet" message="Today's supply events will appear after imports, approvals, emails, confirmations, transport or receiving activity." />
    @endif
</section>

<section id="replenishment-priorities">
    <h2>Replenishment Priorities</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Recommended quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($replenishmentPriorities as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->orderProposal?->supplier?->name }}</td>
                    <td><x-supply.status-badge :status="$item->status" /></td>
                    <td>{{ $item->recommended_quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No replenishment priorities.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="latest-calculation-runs">
    <h2>Latest Calculation Runs</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th>Formula</th>
                <th>Status</th>
                <th>Started by</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($latestCalculationRuns as $run)
                <tr>
                    <td>{{ $run->calculation_date?->toDateString() }}</td>
                    <td>{{ $run->supplier?->name }}</td>
                    <td>{{ $run->formula_version }}</td>
                    <td><x-supply.status-badge :status="$run->status" /></td>
                    <td>{{ $run->startedBy?->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No calculation runs.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="proposals-needing-review">
    <h2>Proposals Needing Review</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Status</th>
                <th>Total lines</th>
                <th>Lines needing review</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proposalsNeedingReview as $proposal)
                <tr>
                    <td>{{ $proposal->supplier?->name }}</td>
                    <td><x-supply.status-badge :status="$proposal->status" /></td>
                    <td>{{ $proposal->total_lines }}</td>
                    <td>{{ $proposal->lines_needing_review_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No proposals needing review.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="supplier-orders-awaiting-action">
    <h2>Supplier Orders Awaiting Action</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Order</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Order date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($supplierOrdersAwaitingAction as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->supplier?->name }}</td>
                    <td><x-supply.status-badge :status="$order->status" /></td>
                    <td>{{ $order->order_date?->toDateString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No supplier orders awaiting action.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="emails-needing-review">
    <h2>Emails Needing Review</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>From</th>
                <th>Subject</th>
                <th>Supplier</th>
                <th>Received</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($emailsNeedingReview as $email)
                <tr>
                    <td>{{ $email->from_email }}</td>
                    <td>{{ $email->subject }}</td>
                    <td>{{ $email->relatedSupplier?->name }}</td>
                    <td>{{ $email->received_at?->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No emails needing review.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="form-autofill-runs-needing-review">
    <h2>Form Autofill Runs Needing Review</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Template</th>
                <th>Email</th>
                <th>Status</th>
                <th>Confidence</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($formAutofillRunsNeedingReview as $run)
                <tr>
                    <td>{{ $run->formTemplate?->name }}</td>
                    <td>{{ $run->emailMessage?->subject }}</td>
                    <td><x-supply.status-badge :status="$run->status" /></td>
                    <td>{{ $run->confidence }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No form autofill runs needing review.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section id="logistics-delays">
    <h2>Logistics Delays</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Order</th>
                <th>Supplier</th>
                <th>Carrier</th>
                <th>Delivery date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logisticsDelays as $record)
                <tr>
                    <td>{{ $record->supplierOrder?->order_number }}</td>
                    <td>{{ $record->supplier?->name }}</td>
                    <td>{{ $record->carrier?->name }}</td>
                    <td>{{ $record->delivery_date?->toDateString() }}</td>
                    <td><x-supply.status-badge :status="$record->status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No logistics delays.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
