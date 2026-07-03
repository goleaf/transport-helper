<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Imports</title>
</head>
<body>
    <main>
        <header>
            <h1>Supply Imports</h1>
            <a href="{{ route('supply.imports.create') }}">Create import</a>
        </header>

        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Successful</th>
                    <th>Failed</th>
                    <th>Rows</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td>{{ $batch->company?->name }}</td>
                        <td>{{ $batch->source_type }}</td>
                        <td>{{ $batch->status instanceof \BackedEnum ? $batch->status->value : $batch->status }}</td>
                        <td>{{ $batch->total_rows }}</td>
                        <td>{{ $batch->successful_rows }}</td>
                        <td>{{ $batch->failed_rows }}</td>
                        <td>{{ $batch->rows_count }}</td>
                        <td><a href="{{ route('supply.imports.show', $batch) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No imports yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $batches->links() }}
    </main>
</body>
</html>
