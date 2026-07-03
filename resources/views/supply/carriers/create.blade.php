@extends('layouts.app')

@section('title')
Create Carrier
@endsection

@section('content')
<header>
    <h1>Create Carrier</h1>
</header>

<form method="post" action="{{ route('supply.carriers.store') }}">
    @csrf
    @include('supply.carriers.partials.form', ['carrier' => null])
    <x-supply.button type="submit">Save carrier</x-supply.button>
</form>
@endsection
