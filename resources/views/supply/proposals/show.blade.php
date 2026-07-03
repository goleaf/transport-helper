@extends('layouts.app')

@section('title')
Order Proposal #{{ $proposal->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.proposals.index') }}">Back to list</a></p>
    <h1>Order Proposal #{{ $proposal->id }}</h1>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
    <section class="warning">
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
        <dd>{{ $proposal->createdBy?->name }}</dd>
        <dt>Approved by</dt>
        <dd>{{ $proposal->approvedBy?->name }}</dd>
        <dt>Approved at</dt>
        <dd>{{ $proposal->approved_at?->toDateTimeString() }}</dd>
    </dl>
</section>

<section class="grid" aria-label="Proposal summary">
    <div class="metric"><span>Total lines</span><strong>{{ $summary['total_lines'] }}</strong></div>
    <div class="metric"><span>Unresolved lines</span><strong>{{ $summary['unresolved_count'] }}</strong></div>
    <div class="metric"><span>Needs review</span><strong>{{ $summary['needs_review_count'] }}</strong></div>
    <div class="metric"><span>Approved</span><strong>{{ $summary['approved_count'] }}</strong></div>
    <div class="metric"><span>Adjusted</span><strong>{{ $summary['adjusted_count'] }}</strong></div>
    <div class="metric"><span>Rejected</span><strong>{{ $summary['rejected_count'] }}</strong></div>
    <div class="metric"><span>Total recommended quantity</span><strong>{{ number_format((float) $summary['total_recommended_quantity'], 3) }}</strong></div>
    <div class="metric"><span>Total approved quantity</span><strong>{{ number_format((float) $summary['total_approved_quantity'], 3) }}</strong></div>
</section>

@if ($summary['unresolved_count'] > 0)
    <p class="warning">This proposal has unresolved items. Resolve draft and needs review lines before proposal approval.</p>
@endif

@if ($summary['orderable_count'] === 0)
    <p class="warning">This proposal has no approved or adjusted positive-quantity lines.</p>
@endif

@if ($proposal->supplierOrder)
    <p class="warning">Converted supplier order: {{ $proposal->supplierOrder->order_number }}</p>
@endif

<section>
    <h2>Actions</h2>
    <div class="actions">
        @if ($canApproveProposal)
            <form method="post" action="{{ route('supply.proposals.approve', $proposal) }}">
                @csrf
                <input type="hidden" name="confirmation" value="1">
                <button type="submit" @disabled(! $summary['can_approve'])>Approve whole proposal</button>
            </form>
        @endif

        @if ($canConvertProposal)
            <form method="post" action="{{ route('supply.proposals.convert-to-supplier-order', $proposal) }}">
                @csrf
                <input type="hidden" name="confirmation" value="1">
                <button type="submit" @disabled(! $summary['can_convert'])>Convert to supplier order</button>
            </form>
        @endif

        <a href="{{ route('supply.proposals.index') }}">Back to list</a>
    </div>

    @if ($summary['blocking_reasons'])
        <p>Blocking reasons: <x-supply.inline-list :items="$summary['blocking_reasons']" /></p>
    @endif
</section>

<section>
    <h2>Status filters</h2>
    <p>
        <a href="{{ route('supply.proposals.show', $proposal) }}">All</a>
        @foreach ($statuses as $status)
            <a href="{{ route('supply.proposals.show', ['proposal' => $proposal, 'status' => $status->value]) }}">
                <x-supply.human-label :value="$status" />
            </a>
        @endforeach
    </p>
    @if ($statusFilter)
        <p>Current filter: {{ $statusFilter }}</p>
    @endif
</section>

<section>
    <h2>Items</h2>
    <table>
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
                    <td><x-supply.table-action :href="route('supply.proposals.items.show', [$proposal, $item])" label="View" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="18">No proposal items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
