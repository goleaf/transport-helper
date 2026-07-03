@extends('layouts.app')

@section('title')
Carriers
@endsection

@section('content')
<header>
    <h1>Carriers</h1>
    <a href="{{ route('supply.carriers.create') }}">Create carrier</a>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<section>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Currency</th>
                <th>Reliability</th>
                <th>Active</th>
                <th>Quotes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($carriers as $carrier)
                <tr>
                    <td>{{ $carrier->name }}</td>
                    <td>{{ $carrier->code }}</td>
                    <td>{{ $carrier->default_currency }}</td>
                    <td>{{ $carrier->reliability_score }}</td>
                    <td>{{ $carrier->is_active ? 'Yes' : 'No' }}</td>
                    <td>{{ $carrier->quotes_count }}</td>
                    <td><a href="{{ route('supply.carriers.show', $carrier) }}">Open</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No carriers.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $carriers->links() }}
</section>
@endsection
