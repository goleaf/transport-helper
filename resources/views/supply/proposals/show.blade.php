<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Proposal {{ $proposal->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.proposals.index') }}">Back to proposals</a></p>
            <h1>Supply Proposal {{ $proposal->id }}</h1>
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
                <dd>{{ $proposal->supplier?->name }}</dd>

                <dt>Formula version</dt>
                <dd>{{ $proposal->calculationRun?->formula_version }}</dd>

                <dt>Calculation date</dt>
                <dd>{{ $proposal->calculationRun?->calculation_date?->toDateString() }}</dd>

                <dt>Status</dt>
                <dd>{{ $proposal->status instanceof \BackedEnum ? $proposal->status->value : $proposal->status }}</dd>

                <dt>Created by</dt>
                <dd>{{ $proposal->createdBy?->name }}</dd>

                <dt>Approved by</dt>
                <dd>{{ $proposal->approvedBy?->name }}</dd>
            </dl>
        </section>

        <section>
            <h2>Actions</h2>
            @if ($canApproveProposal)
                <form method="post" action="{{ route('supply.proposals.approve', $proposal) }}">
                    @csrf
                    <button type="submit">Approve proposal</button>
                </form>
            @endif

            @if ($canConvertProposal)
                <form method="post" action="{{ route('supply.proposals.convert-to-supplier-order', $proposal) }}">
                    @csrf
                    <button type="submit">Convert to supplier order</button>
                </form>
            @endif
        </section>

        <section>
            <h2>Status filters</h2>
            <p>
                <a href="{{ route('supply.proposals.show', $proposal) }}">All</a>
                @foreach ($statuses as $status)
                    <a href="{{ route('supply.proposals.show', ['proposal' => $proposal, 'status' => $status->value]) }}">
                        {{ $status->value }}
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
                        <th>Product</th>
                        <th>Status</th>
                        <th>Recommended quantity</th>
                        <th>Approved quantity</th>
                        <th>Human review</th>
                        <th>Warnings</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->status instanceof \BackedEnum ? $item->status->value : $item->status }}</td>
                            <td>{{ $item->recommended_quantity }}</td>
                            <td>{{ $item->approved_quantity }}</td>
                            <td>{{ $item->requires_human_review ? 'Yes' : 'No' }}</td>
                            <td>
                                @forelse (($item->warnings_json ?? []) as $warning)
                                    <span>{{ is_scalar($warning) ? $warning : json_encode($warning) }}</span>
                                @empty
                                    <span>None</span>
                                @endforelse
                            </td>
                            <td>
                                <a href="{{ route('supply.proposals.items.show', [$proposal, $item]) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No proposal items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
