@extends('layouts.app')

@section('title')
Edit Pilot Supplier
@endsection

@section('content')
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">Edit Pilot Supplier</h1>

        <form method="POST" action="{{ route('supply.pilots.update', $pilot) }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @method('PATCH')
            @include('supply.pilots.partials.form', ['pilot' => $pilot])
            <div>
                <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
                <input id="status" name="status" class="input input-bordered input-primary mt-1 w-full" value="{{ old('status', $pilot->status) }}">
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="data_sources_json_text">Data sources JSON</label>
                <textarea id="data_sources_json_text" name="data_sources_json_text" rows="5" class="textarea textarea-bordered textarea-primary mt-1 w-full font-mono text-sm">{{ old('data_sources_json_text', $dataSourcesText) }}</textarea>
            </div>
            <x-supply.button type="submit">Update pilot</x-supply.button>
        </form>
    </div>
@endsection
