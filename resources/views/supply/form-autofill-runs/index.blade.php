@extends('layouts.app')

@section('title')
Form Autofill Runs
@endsection

@section('content')
<h1>Form Autofill Runs</h1>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Template</th>
            <th>Status</th>
            <th>Confidence</th>
            <th>Fields</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($runs as $run)
            <tr>
                <td>{{ $run->id }}</td>
                <td>{{ $run->emailMessage?->subject }}</td>
                <td>{{ $run->formTemplate?->name }}</td>
                <td><x-supply.status-badge :status="$run->status" /></td>
                <td>{{ $run->confidence }}</td>
                <td>{{ $run->field_values_count }}</td>
                <td><x-supply.table-action :href="route('supply.form-autofill-runs.show', $run)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No form autofill runs.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $runs->links() }}
@endsection
