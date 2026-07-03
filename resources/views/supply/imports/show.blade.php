@extends('layouts.app')

@section('title')
Supply Import {{ $batch->id }}
@endsection

@section('content')
<header>
    <h1>Supply Import {{ $batch->id }}</h1>
    <a href="{{ route('supply.imports.index') }}">Back</a>
</header>

<dl>
    <dt>Company</dt>
    <dd>{{ $batch->company?->name }}</dd>
    <dt>Import type</dt>
    <dd><x-supply.human-label :value="$batch->import_type" /></dd>
    <dt>Source type</dt>
    <dd>{{ $batch->source_type }}</dd>
    <dt>Adapter</dt>
    <dd><x-supply.human-label :value="$batch->adapter" /></dd>
    <dt>Filename</dt>
    <dd>{{ $batch->original_filename }}</dd>
    <dt>Status</dt>
    <dd><x-supply.status-badge :status="$batch->status" /></dd>
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
            <th>Original data</th>
            <th>Cleaned data</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row->row_number }}</td>
                <td><x-supply.status-badge :status="$row->status" /></td>
                <td>{{ $row->error_message }}</td>
                <td>{{ $row->related_model_type }} {{ $row->related_model_id }}</td>
                <td><x-supply.structured-value :value="$row->raw_json" /></td>
                <td><x-supply.structured-value :value="$row->normalized_json" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No rows.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $rows->links() }}
@endsection
