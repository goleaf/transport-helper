@extends('layouts.app')

@section('title')
Upload Manufacturer Form
@endsection

@section('content')
    <div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">Upload Manufacturer Form</h1>
        <form method="POST" enctype="multipart/form-data" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            <input type="file" name="file" class="file-input file-input-bordered file-input-primary w-full">
            <x-supply.button type="submit">Upload</x-supply.button>
        </form>
    </div>
@endsection
