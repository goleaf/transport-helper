@extends('layouts.app')

@section('title')
Supply Health
@endsection

@section('content')
<header>
    <h1>Supply Health</h1>
    <p>Status: {{ $result['status'] }}</p>
</header>

<table class="table table-zebra">
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($result['checks'] as $check)
            <tr>
                <td>{{ $check['name'] }}</td>
                <td>{{ $check['status'] }}</td>
                <td>{{ $check['message'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">No checks.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
