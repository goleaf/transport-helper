<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Proposals</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; color: #17202a; }
        main { max-width: 1280px; margin: 0 auto; padding: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d7dde5; padding: 10px; text-align: left; vertical-align: top; }
        form.filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 18px 0; }
        label { display: grid; gap: 4px; font-size: 14px; }
        input, select, button { font: inherit; padding: 8px; }
        .actions { display: flex; gap: 10px; align-items: center; }
    </style>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>Order Proposals</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form class="filters" method="get" action="{{ route('supply.proposals.index') }}">
            <label>
                Status
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($statusFilter === $status->value)>{{ str_replace('_', ' ', $status->value) }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Supplier
                <select name="supplier_id">
                    <option value="">All suppliers</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Calculation date from
                <input name="calculation_date_from" type="date" value="{{ $filters['calculation_date_from'] ?? '' }}">
            </label>

            <label>
                Calculation date to
                <input name="calculation_date_to" type="date" value="{{ $filters['calculation_date_to'] ?? '' }}">
            </label>

            <label>
                Needs review
                <select name="needs_review">
                    <option value="">Any</option>
                    <option value="1" @selected((string) ($filters['needs_review'] ?? '') === '1')>Yes</option>
                </select>
            </label>

            <div class="actions">
                <button type="submit">Filter</button>
                <a href="{{ route('supply.proposals.index') }}">Clear filters</a>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier</th>
                    <th>Calculation date</th>
                    <th>Formula version</th>
                    <th>Status</th>
                    <th>Total lines</th>
                    <th>Needs review</th>
                    <th>Approved</th>
                    <th>Adjusted</th>
                    <th>Rejected</th>
                    <th>Total recommended quantity</th>
                    <th>Total approved quantity</th>
                    <th>Created by</th>
                    <th>Approved by</th>
                    <th>Approved at</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proposals as $proposal)
                    <tr>
                        <td>{{ $proposal->id }}</td>
                        <td>{{ $proposal->supplier?->name }}</td>
                        <td>{{ $proposal->calculationRun?->calculation_date?->toDateString() }}</td>
                        <td>{{ $proposal->calculationRun?->formula_version }}</td>
                        <td>@include('supply.proposals.partials.status-badge', ['status' => $proposal->status])</td>
                        <td>{{ $proposal->total_lines ?: $proposal->items_count }}</td>
                        <td>{{ $proposal->needs_review_count }}</td>
                        <td>{{ $proposal->approved_count }}</td>
                        <td>{{ $proposal->adjusted_count }}</td>
                        <td>{{ $proposal->rejected_count }}</td>
                        <td>{{ number_format((float) ($proposal->total_recommended_quantity ?? 0), 3) }}</td>
                        <td>{{ number_format((float) ($proposal->total_approved_quantity ?? 0), 3) }}</td>
                        <td>{{ $proposal->createdBy?->name }}</td>
                        <td>{{ $proposal->approvedBy?->name }}</td>
                        <td>{{ $proposal->approved_at?->toDateTimeString() }}</td>
                        <td><a href="{{ route('supply.proposals.show', $proposal) }}">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16">No order proposals yet. Run calculation first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $proposals->links() }}
    </main>
</body>
</html>
