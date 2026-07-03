@extends('layouts.app')

@section('title')
AI Email Extractions
@endsection

@section('content')
<header>
    <h1>AI Email Extractions</h1>
</header>

<form method="get" action="{{ route('supply.ai-extractions.index') }}">
    <label>
        Provider
        <input class="input input-bordered input-primary" name="provider" value="{{ request('provider') }}">
    </label>
    <label>
        Requires review
        <input class="checkbox checkbox-primary" type="checkbox" name="requires_human_review" value="1" @checked(request()->boolean('requires_human_review'))>
    </label>
    <label>
        Accepted
        <input class="checkbox checkbox-primary" type="checkbox" name="accepted" value="1" @checked(request()->boolean('accepted'))>
    </label>
    <label>
        Rejected
        <input class="checkbox checkbox-primary" type="checkbox" name="rejected" value="1" @checked(request()->boolean('rejected'))>
    </label>
    <x-supply.button type="submit">Filter</x-supply.button>
</form>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>ID</th>
            <th>Email subject</th>
            <th>Provider</th>
            <th>Email type</th>
            <th>Confidence</th>
            <th>Review</th>
            <th>Review reason</th>
            <th>Accepted</th>
            <th>Rejected</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($extractions as $extraction)
            <tr>
                <td>{{ $extraction->id }}</td>
                <td>{{ $extraction->emailMessage?->subject }}</td>
                <td>{{ $extraction->provider }}</td>
                <td>{{ $extraction->output_json['email_type'] ?? 'unclear' }}</td>
                <td>{{ $extraction->confidence }}</td>
                <td>@include('supply.ai-extractions.partials.status-badge', ['extraction' => $extraction])</td>
                <td>{{ $extraction->review_reason }}</td>
                <td>{{ $extraction->accepted_at }}</td>
                <td>{{ $extraction->rejected_at }}</td>
                <td><x-supply.table-action :href="route('supply.ai-extractions.show', $extraction)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No AI email extractions yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $extractions->links() }}
@endsection
