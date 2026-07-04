@extends('layouts.app')

@section('title')
Merge Proposal
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Merge proposal</p>
        <h1><x-supply.human-label :label="$proposal->merge_type" /> merge</h1>
    </div>
    <x-supply.button :href="route('supply.master-data.merge-proposals.index')">Back to proposals</x-supply.button>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<x-supply.alert tone="warning">
    No automatic merge is performed. Execution requires approved status and explicit confirmation. Source records with history are not hard-deleted.
</x-supply.alert>

<section class="grid gap-4 md:grid-cols-2">
    <x-supply.card>
        <h2>Source</h2>
        @foreach ($impact['source'] as $label => $value)
            <p><strong><x-supply.human-label :label="$label" />:</strong> {{ $value ?: 'Not set' }}</p>
        @endforeach
    </x-supply.card>
    <x-supply.card>
        <h2>Target</h2>
        @foreach ($impact['target'] as $label => $value)
            <p><strong><x-supply.human-label :label="$label" />:</strong> {{ $value ?: 'Not set' }}</p>
        @endforeach
    </x-supply.card>
</section>

<section>
    <h2>Affected Tables</h2>
    <table class="table table-zebra">
        <tbody>
            @forelse ($affectedRows as $row)
                <tr>
                    <th>{{ $row['label'] }}</th>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @empty
                <tr>
                    <td>No affected table rows detected.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="grid gap-4 md:grid-cols-3">
    <form method="POST" action="{{ route('supply.master-data.merge-proposals.approve', $proposal) }}">
        @csrf
        <label>Approval note
            <textarea class="textarea textarea-bordered" name="note" required>Approved after impact preview.</textarea>
        </label>
        <x-supply.button type="submit">Approve proposal</x-supply.button>
    </form>
    <form method="POST" action="{{ route('supply.master-data.merge-proposals.reject', $proposal) }}">
        @csrf
        <label>Rejection reason
            <textarea class="textarea textarea-bordered" name="reason" required></textarea>
        </label>
        <input type="hidden" name="note" value="Rejected">
        <x-supply.button type="submit" variant="warning" mode="outline">Reject proposal</x-supply.button>
    </form>
    <form method="POST" action="{{ route('supply.master-data.merge-proposals.execute', $proposal) }}">
        @csrf
        <input type="hidden" name="confirmation" value="1">
        <p>Execution updates safe references and marks source as merged.</p>
        <x-supply.button type="submit" variant="accent">Execute approved merge</x-supply.button>
    </form>
</section>
@endsection
