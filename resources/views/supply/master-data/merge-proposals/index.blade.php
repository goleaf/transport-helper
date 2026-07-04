@extends('layouts.app')

@section('title')
Master Data Merge Proposals
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Master data governance</p>
        <h1>Merge Proposals</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.master-data.partials.tabs')

<x-supply.alert tone="warning">
    Merge proposals only preview impact. Records are not merged until approval and explicit execution.
</x-supply.alert>

<section>
    <h2>Create Merge Proposal</h2>
    <form method="POST" action="{{ route('supply.master-data.merge-proposals.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label>Merge type
            <select name="merge_type" class="select select-bordered" required>
                <option value="product">Product</option>
                <option value="supplier">Supplier</option>
            </select>
        </label>
        <label>Source ID
            <input class="input input-bordered" name="source_id" type="number" required>
        </label>
        <label>Target ID
            <input class="input input-bordered" name="target_id" type="number" required>
        </label>
        <label class="md:col-span-2">Reason
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>
        <div class="md:col-span-2">
            <x-supply.button type="submit">Create merge proposal</x-supply.button>
        </div>
    </form>
</section>

<section>
    <h2>Proposals</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Type</th>
                <th>Source</th>
                <th>Target</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proposals as $proposal)
                <tr>
                    <td><x-supply.human-label :label="$proposal->merge_type" /></td>
                    <td>{{ $proposal->source_model_id }}</td>
                    <td>{{ $proposal->target_model_id }}</td>
                    <td><x-supply.status-badge :status="$proposal->status" /></td>
                    <td>{{ $proposal->reason }}</td>
                    <td><x-supply.table-action :href="route('supply.master-data.merge-proposals.show', $proposal)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No merge proposals yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $proposals->links() }}
</section>
@endsection
