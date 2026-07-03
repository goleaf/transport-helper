@extends('layouts.app')

@section('title')
Pilot UAT Checklist
@endsection

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Pilot UAT Checklist</h1>
            <p class="text-sm text-gray-600">{{ $pilot->supplier?->name }} · {{ $pilot->company?->name }}</p>
        </div>

        @include('supply.pilots.partials.uat-checklist-panel', ['pilot' => $pilot, 'checklist' => $checklist, 'evaluation' => $evaluation])
    </div>
@endsection
