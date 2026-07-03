<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Proposal Item: {{ $item->product?->sku }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; color: #17202a; }
        main { max-width: 1180px; margin: 0 auto; padding: 24px; }
        section { margin: 24px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d7dde5; padding: 10px; text-align: left; vertical-align: top; }
        textarea, input, button { font: inherit; padding: 8px; width: 100%; box-sizing: border-box; }
        form { border: 1px solid #d7dde5; padding: 12px; margin: 12px 0; display: grid; gap: 10px; }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; background: #f7f9fb; padding: 12px; }
        .warning { border-left: 4px solid #b25b00; background: #fff8ed; padding: 12px; }
    </style>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.proposals.show', $proposal) }}">Back to proposal</a></p>
            <h1>Order Proposal Item: {{ $item->product?->sku }}</h1>
            <p>{{ $item->product?->name }} · {{ $proposal->supplier?->name }} · Proposal #{{ $proposal->id }}</p>
            <p>
                @include('supply.proposals.partials.status-badge', ['status' => $item->status])
                @if ($item->requires_human_review)
                    <strong>Human review required</strong>
                @endif
            </p>
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

        @include('supply.proposals.partials.timeline', ['item' => $item])
        @include('supply.proposals.partials.formula-summary', ['item' => $item])

        <section>
            <h2>Explanation</h2>
            @include('supply.proposals.partials.explanation', ['explanation' => $item->explanation_json ?? []])
        </section>

        <section>
            <h2>Warnings</h2>
            @if ($item->requires_human_review)
                <p class="warning">Human review required.</p>
            @endif
            <ul>
                @forelse (($item->warnings_json ?? []) as $warning)
                    <li>{{ is_scalar($warning) ? $warning : json_encode($warning, JSON_UNESCAPED_SLASHES) }}</li>
                @empty
                    <li>No warnings.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2>Actions</h2>
            @include('supply.proposals.partials.item-actions', [
                'proposal' => $proposal,
                'item' => $item,
                'canApproveItem' => $canApproveItem,
                'canAdjustItem' => $canAdjustItem,
                'canRejectItem' => $canRejectItem,
                'isConverted' => $isConverted,
            ])
        </section>

        <section>
            <h2>Audit history</h2>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>User</th>
                        <th>Metadata</th>
                        <th>Created at</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->event_type }}</td>
                            <td>{{ $auditLog->user?->name }}</td>
                            <td><pre>{{ json_encode($auditLog->metadata_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                            <td>{{ $auditLog->created_at?->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No audit events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
