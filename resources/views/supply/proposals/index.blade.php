<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Proposals</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>Supply Proposals</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Calculation date</th>
                    <th>Status</th>
                    <th>Total lines</th>
                    <th>Lines needing review</th>
                    <th>Total recommended quantity</th>
                    <th>Created by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proposals as $proposal)
                    <tr>
                        <td>{{ $proposal->supplier?->name }}</td>
                        <td>{{ $proposal->calculationRun?->calculation_date?->toDateString() }}</td>
                        <td>{{ $proposal->status instanceof \BackedEnum ? $proposal->status->value : $proposal->status }}</td>
                        <td>{{ $proposal->total_lines ?: $proposal->items_count }}</td>
                        <td>{{ $proposal->lines_needing_review_count }}</td>
                        <td>{{ number_format((float) ($proposal->total_recommended_quantity ?? 0), 3) }}</td>
                        <td>{{ $proposal->createdBy?->name }}</td>
                        <td>
                            <a href="{{ route('supply.proposals.show', $proposal) }}">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No proposals yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $proposals->links() }}
    </main>
</body>
</html>
