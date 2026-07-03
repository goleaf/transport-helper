<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Import {{ $batch->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <h1>Supply Import {{ $batch->id }}</h1>
            <a href="{{ route('supply.imports.index') }}">Back</a>
        </header>

        <dl>
            <dt>Company</dt>
            <dd>{{ $batch->company?->name }}</dd>
            <dt>Type</dt>
            <dd>{{ $batch->source_type }}</dd>
            <dt>Status</dt>
            <dd>{{ $batch->status instanceof \BackedEnum ? $batch->status->value : $batch->status }}</dd>
            <dt>Total</dt>
            <dd>{{ $batch->total_rows }}</dd>
            <dt>Successful</dt>
            <dd>{{ $batch->successful_rows }}</dd>
            <dt>Failed</dt>
            <dd>{{ $batch->failed_rows }}</dd>
            <dt>Summary</dt>
            <dd>{{ $batch->error_summary }}</dd>
        </dl>

        <form method="POST" action="{{ route('supply.imports.rollback', $batch) }}">
            @csrf
            <button type="submit">Rollback</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Row</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Related</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batch->rows as $row)
                    <tr>
                        <td>{{ $row->row_number }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->error_message }}</td>
                        <td>{{ $row->related_model_type }} {{ $row->related_model_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No rows.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
