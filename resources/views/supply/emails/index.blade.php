@extends('layouts.app')

@section('title')
Supply Emails
@endsection

@section('content')
<header>
    <h1>Supply Emails</h1>
    <p><a href="{{ route('supply.emails.create-manual') }}">Manual inbound email</a></p>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<form method="get" action="{{ route('supply.emails.index') }}">
    <label>
        Direction
        <select class="select select-bordered select-primary" name="direction">
            <option value="">Any</option>
            <option value="inbound" @selected(request('direction') === 'inbound')>Inbound</option>
            <option value="outbound" @selected(request('direction') === 'outbound')>Outbound</option>
        </select>
    </label>

    <label>
        Status
        <input class="input input-bordered input-primary" name="status" value="{{ request('status') }}">
    </label>

    <label>
        From email
        <input class="input input-bordered input-primary" name="from_email" value="{{ request('from_email') }}">
    </label>

    <label>
        Needs review
        <input class="checkbox checkbox-primary" type="checkbox" name="needs_review" value="1" @checked(request()->boolean('needs_review'))>
    </label>

    <x-supply.button type="submit">Filter</x-supply.button>
</form>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>Direction</th>
            <th>From</th>
            <th>Subject</th>
            <th>Supplier</th>
            <th>Order</th>
            <th>Status</th>
            <th>AI extractions</th>
            <th>Attachments</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($emails as $email)
            <tr>
                <td><x-supply.human-label :value="$email->direction" /></td>
                <td>{{ $email->from_email }}</td>
                <td>{{ $email->subject }}</td>
                <td>{{ $email->relatedSupplier?->name }}</td>
                <td>{{ $email->relatedSupplierOrder?->order_number }}</td>
                <td><x-supply.status-badge :status="$email->status" /></td>
                <td>{{ $email->ai_email_extractions_count }}</td>
                <td>{{ $email->attachments_count }}</td>
                <td><x-supply.table-action :href="route('supply.emails.show', $email)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No emails yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $emails->links() }}
@endsection
