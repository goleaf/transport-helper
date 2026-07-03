@extends('layouts.app')

@section('title')
Notifications
@endsection

@section('content')
<header>
    <h1>Notifications</h1>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<form method="post" action="{{ route('supply.notifications.read-all') }}">
    @csrf
    <x-supply.button type="submit">Mark all as read</x-supply.button>
</form>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>Status</th>
            <th>Type</th>
            <th>Title</th>
            <th>Message</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($notifications as $notification)
            <tr>
                <td>{{ $notification->read_at ? 'read' : 'unread' }}</td>
                <td>{{ $notification->data['type'] ?? $notification->type }}</td>
                <td>{{ $notification->data['title'] ?? '' }}</td>
                <td>{{ $notification->data['message'] ?? '' }}</td>
                <td>{{ $notification->created_at?->toDateTimeString() }}</td>
                <td>
                    @if ($notification->read_at === null)
                        <form method="post" action="{{ route('supply.notifications.read', $notification->id) }}">
                            @csrf
                            <x-supply.button type="submit">Mark read</x-supply.button>
                        </form>
                    @else
                        Read
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No notifications.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $notifications->links() }}
@endsection
