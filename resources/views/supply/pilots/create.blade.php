@extends('layouts.app')

@section('title')
New Pilot Supplier
@endsection

@section('content')
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">New Pilot Supplier</h1>

        <form method="POST" action="{{ route('supply.pilots.store') }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @include('supply.pilots.partials.form', ['pilot' => null])
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="allow_multiple" value="1" class="checkbox checkbox-primary">
                Allow another active pilot for this supplier
            </label>
            <x-supply.button type="submit">Create pilot</x-supply.button>
        </form>
    </div>
@endsection
