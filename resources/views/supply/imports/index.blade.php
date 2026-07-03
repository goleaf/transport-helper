@extends('layouts.app')

@section('title')
Supply Imports
@endsection

@section('content')
<header>
    <h1>Supply Imports</h1>
    <a href="{{ route('supply.imports.create') }}">Create import</a>
</header>

<form method="GET" action="{{ route('supply.imports.index') }}">
    <label>
        Status
        <input class="input input-bordered input-primary" name="status" value="{{ $filters['status'] ?? '' }}">
    </label>
    <label>
        Import type
        <input class="input input-bordered input-primary" name="import_type" value="{{ $filters['import_type'] ?? '' }}">
    </label>
    <x-supply.button type="submit">Filter</x-supply.button>
    <a href="{{ route('supply.imports.index') }}">Clear</a>
</form>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>ID</th>
            <th>Company</th>
            <th>Import type</th>
            <th>Source</th>
            <th>Adapter</th>
            <th>Filename</th>
            <th>Status</th>
            <th>Total</th>
            <th>Successful</th>
            <th>Failed</th>
            <th>Rows</th>
            <th>Started</th>
            <th>Finished</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($batches as $batch)
            <tr>
                <td>{{ $batch->id }}</td>
                <td>{{ $batch->company?->name }}</td>
                <td><x-supply.human-label :value="$batch->import_type" /></td>
                <td>{{ $batch->source_type }}</td>
                <td><x-supply.human-label :value="$batch->adapter" /></td>
                <td>{{ $batch->original_filename }}</td>
                <td><x-supply.status-badge :status="$batch->status" /></td>
                <td>{{ $batch->total_rows }}</td>
                <td>{{ $batch->successful_rows }}</td>
                <td>{{ $batch->failed_rows }}</td>
                <td>{{ $batch->rows_count }}</td>
                <td>{{ $batch->started_at }}</td>
                <td>{{ $batch->finished_at }}</td>
                <td><x-supply.table-action :href="route('supply.imports.show', $batch)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="13">No imports yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $batches->links() }}
@endsection
