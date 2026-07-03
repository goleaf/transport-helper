<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Proposal #{{ $proposal->id }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; color: #17202a; }
        main { max-width: 1280px; margin: 0 auto; padding: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d7dde5; padding: 10px; text-align: left; vertical-align: top; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 18px 0; }
        .metric { border: 1px solid #d7dde5; padding: 12px; }
        .metric strong { display: block; font-size: 22px; }
        .warning { border-left: 4px solid #b25b00; background: #fff8ed; padding: 12px; margin: 12px 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0; }
        button, select { font: inherit; padding: 8px; }
        button[disabled] { opacity: .55; cursor: not-allowed; }
    </style>
</head>
<body>
    <main>
        <x-supply.navigation />

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

            @if (! empty($summary['blocking_reasons']))
                <p>Blocking reasons: {{ implode(', ', $summary['blocking_reasons']) }}</p>
            @endif
        </section>

        <section>
            <h2>Status filters</h2>
            <p>
                <a href="{{ route('supply.proposals.show', $proposal) }}">All</a>
                @foreach ($statuses as $status)
                    <a href="{{ route('supply.proposals.show', ['proposal' => $proposal, 'status' => $status->value]) }}">
                        {{ str_replace('_', ' ', $status->value) }}
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
                            <td>
                                @php($warnings = $item->warnings_json ?? [])
                                {{ count($warnings) }} warning{{ count($warnings) === 1 ? '' : 's' }}
                                @if (count($warnings) > 0)
                                    <br>{{ is_scalar($warnings[0] ?? null) ? $warnings[0] : json_encode($warnings[0] ?? []) }}
                                @endif
                            </td>
                            <td><a href="{{ route('supply.proposals.items.show', [$proposal, $item]) }}">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18">No proposal items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
