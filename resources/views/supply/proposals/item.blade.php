<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Proposal Item {{ $item->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.proposals.show', $proposal) }}">Back to proposal</a></p>
            <h1>{{ $item->product?->sku }} - {{ $item->product?->name }}</h1>
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
            <h2>T0/T1/T2/T3 timeline</h2>
            <dl>
                <dt>T0</dt>
                <dd>{{ $item->t0_date?->toDateString() }}</dd>

                <dt>T1</dt>
                <dd>{{ $item->t1_date?->toDateString() }}</dd>

                <dt>T2</dt>
                <dd>{{ $item->t2_date?->toDateString() }}</dd>

                <dt>T3</dt>
                <dd>{{ $item->t3_date?->toDateString() }}</dd>
            </dl>
        </section>

        <section>
            <h2>Formula components</h2>
            <table>
                <tbody>
                    <tr>
                        <th>trend</th>
                        <td>{{ $item->trend }}</td>
                    </tr>
                    <tr>
                        <th>need_t0_t1</th>
                        <td>{{ $item->need_t0_t1 }}</td>
                    </tr>
                    <tr>
                        <th>stock_t1</th>
                        <td>{{ $item->stock_t1 }}</td>
                    </tr>
                    <tr>
                        <th>need_t1_t2</th>
                        <td>{{ $item->need_t1_t2 }}</td>
                    </tr>
                    <tr>
                        <th>safety_stock</th>
                        <td>{{ $item->safety_stock }}</td>
                    </tr>
                    <tr>
                        <th>inbound_until_t1</th>
                        <td>{{ $item->inbound_until_t1 }}</td>
                    </tr>
                    <tr>
                        <th>inbound_t1_t3</th>
                        <td>{{ $item->inbound_t1_t3 }}</td>
                    </tr>
                    <tr>
                        <th>reserved_quantity</th>
                        <td>{{ $item->reserved_quantity }}</td>
                    </tr>
                    <tr>
                        <th>raw_need</th>
                        <td>{{ $item->raw_need }}</td>
                    </tr>
                    <tr>
                        <th>recommended_quantity</th>
                        <td>{{ $item->recommended_quantity }}</td>
                    </tr>
                    <tr>
                        <th>approved_quantity</th>
                        <td>{{ $item->approved_quantity }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Warnings</h2>
            @if ($item->requires_human_review)
                <p>Human review required.</p>
            @endif
            <ul>
                @forelse (($item->warnings_json ?? []) as $warning)
                    <li>{{ is_scalar($warning) ? $warning : json_encode($warning) }}</li>
                @empty
                    <li>No warnings.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2>Explanation</h2>
            <pre>{{ json_encode($item->explanation_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        <section>
            <h2>Decisions</h2>
            @if ($canApproveItem)
                <form method="post" action="{{ route('supply.proposals.items.approve', [$proposal, $item]) }}">
                    @csrf
                    <button type="submit">Approve</button>
                </form>
            @endif

            @if ($canAdjustItem)
                <form method="post" action="{{ route('supply.proposals.items.adjust', [$proposal, $item]) }}">
                    @csrf
                    <label for="quantity">Adjusted quantity</label>
                    <input id="quantity" name="quantity" type="number" min="0" step="0.001" value="{{ old('quantity', $item->approved_quantity ?? $item->recommended_quantity) }}">

                    <label for="reason">Adjustment reason</label>
                    <textarea id="reason" name="reason">{{ old('reason') }}</textarea>

                    <button type="submit">Adjust</button>
                </form>
            @endif

            @if ($canRejectItem)
                <form method="post" action="{{ route('supply.proposals.items.reject', [$proposal, $item]) }}">
                    @csrf
                    <button type="submit">Reject</button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
