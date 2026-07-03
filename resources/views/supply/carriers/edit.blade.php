@extends('layouts.app')

@section('title')
Edit Carrier
@endsection

@section('content')
<header>
    <h1>Edit Carrier</h1>
</header>

<form method="post" action="{{ route('supply.carriers.update', $carrier) }}">
    @csrf
    @method('PATCH')
    @include('supply.carriers.partials.form', ['carrier' => $carrier])
    <x-supply.button type="submit">Save carrier</x-supply.button>
</form>
@endsection
