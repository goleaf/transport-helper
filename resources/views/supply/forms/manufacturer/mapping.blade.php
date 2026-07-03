@extends('layouts.app')

@section('title')
Manufacturer Form Mapping
@endsection

@section('content')
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">Manufacturer Form Mapping</h1>
        @include('supply.forms.manufacturer.partials.mapping-help')
    </div>
@endsection
