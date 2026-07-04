@extends('layouts.app')

@section('title')
Master Data Change Request
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Change request</p>
        <h1><x-supply.human-label :label="$changeRequest->request_type" /></h1>
    </div>
    <x-supply.button :href="route('supply.master-data.change-requests.index')">Back to requests</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<section class="grid gap-4 md:grid-cols-2">
    <x-supply.card>
        <h2>Status</h2>
        <p><strong>Status:</strong> <x-supply.status-badge :status="$changeRequest->status" /></p>
        <p><strong>Reason:</strong> {{ $changeRequest->reason }}</p>
        <p><strong>Requested by:</strong> {{ $changeRequest->requestedBy?->name ?: 'System' }}</p>
        <p><strong>Approved by:</strong> {{ $changeRequest->approvedBy?->name ?: 'Not approved' }}</p>
    </x-supply.card>
    <x-supply.card>
        <h2>Requested Changes</h2>
        <table class="table">
            <tbody>
                @forelse ($changeRows as $row)
                    <tr>
                        <th>{{ $row['label'] }}</th>
                        <td>{{ $row['value'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>No structured change values were provided.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-supply.card>
</section>

<section class="grid gap-4 md:grid-cols-3">
    <form method="POST" action="{{ route('supply.master-data.change-requests.approve', $changeRequest) }}">
        @csrf
        <label>Approval note
            <textarea class="textarea textarea-bordered" name="note" required>Approved for controlled application.</textarea>
        </label>
        <x-supply.button type="submit">Approve</x-supply.button>
    </form>
    <form method="POST" action="{{ route('supply.master-data.change-requests.reject', $changeRequest) }}">
        @csrf
        <label>Rejection reason
            <textarea class="textarea textarea-bordered" name="reason" required></textarea>
        </label>
        <x-supply.button type="submit" variant="warning" mode="outline">Reject</x-supply.button>
    </form>
    <form method="POST" action="{{ route('supply.master-data.change-requests.apply', $changeRequest) }}">
        @csrf
        <p>Applying is allowed only after approval.</p>
        <x-supply.button type="submit" variant="accent">Apply approved change</x-supply.button>
    </form>
</section>
@endsection
