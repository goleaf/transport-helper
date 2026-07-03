@extends('layouts.app')

@section('title')
Real Data Onboarding
@endsection

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Real Data Onboarding</h1>
            <p class="text-sm text-gray-600">Checklist for moving from dry-run samples to approved real data.</p>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="table table-zebra">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($checklist['items'] as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item['label'] }}</td>
                            <td class="px-4 py-3">{{ $item['status'] }}</td>
                            <td class="px-4 py-3">{{ $item['message'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No checklist items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
