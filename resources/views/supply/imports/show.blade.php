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
            <dt>Import type</dt>
            <dd>{{ $batch->import_type }}</dd>
            <dt>Source type</dt>
            <dd>{{ $batch->source_type }}</dd>
            <dt>Adapter</dt>
            <dd>{{ $batch->adapter }}</dd>
            <dt>Filename</dt>
            <dd>{{ $batch->original_filename }}</dd>
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

        @if ($canRollback)
            <form method="POST" action="{{ route('supply.imports.rollback', $batch) }}">
                @csrf
                <button type="submit">Rollback</button>
            </form>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Row</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Related</th>
                    <th>Raw</th>
                    <th>Normalized</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->row_number }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->error_message }}</td>
                        <td>{{ $row->related_model_type }} {{ $row->related_model_id }}</td>
                        <td><pre>{{ json_encode($row->raw_json, JSON_PRETTY_PRINT) }}</pre></td>
                        <td><pre>{{ json_encode($row->normalized_json, JSON_PRETTY_PRINT) }}</pre></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No rows.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $rows->links() }}
    </main>
</body>
</html>
