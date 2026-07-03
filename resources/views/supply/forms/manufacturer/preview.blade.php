@extends('layouts.app')

@section('title')
Manufacturer Form Preview
@endsection

@section('content')
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900">Manufacturer Form Preview</h1>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h2 class="font-semibold text-gray-900">Header</h2>
            <dl class="mt-3 grid gap-3 md:grid-cols-3">
                @forelse ($preview['header'] as $key => $value)
                    <div>
                        <dt class="text-xs uppercase text-gray-500">{{ $key }}</dt>
                        <dd class="text-sm text-gray-900">{{ $value }}</dd>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No header values.</div>
                @endforelse
            </dl>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="table table-zebra">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($preview['items'] as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item['sku'] }}</td>
                            <td class="px-4 py-3">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3">{{ $item['ordered_quantity'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
