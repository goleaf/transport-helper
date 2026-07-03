@extends('layouts.app')

@section('title')
Procurement Gate Result
@endsection

@section('content')
<x-supply.page-header title="Procurement Gate Result" subtitle="Gate checks only. It does not perform the business action." :back-url="route('supply.procurement.reports.index')" />

@include('supply.procurement.partials.tabs')

<x-supply.alert tone="info">This gate check did not approve proposals, create supplier orders, send emails or select carriers.</x-supply.alert>

<section>
    <dl class="structured-data">
        <dt>Subject type</dt>
        <dd><x-supply.human-label :value="$subjectType" /></dd>
        <dt>Subject ID</dt>
        <dd>#{{ $subject->getKey() }}</dd>
    </dl>
</section>

@include('supply.procurement.partials.gate-result', ['result' => $result])

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Line estimates</p>
            <h2>Value details</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Unit price</th>
                <th>Line total</th>
                <th>Source</th>
                <th>Warnings</th>
            </tr>
        </thead>
        <tbody>
            @forelse (data_get($result, 'estimated_value.lines', []) as $line)
                <tr>
                    <td>{{ $line['sku'] ?? 'No SKU' }}</td>
                    <td>{{ number_format((float) ($line['quantity'] ?? 0), 3) }}</td>
                    <td>{{ ($line['unit_price'] ?? null) !== null ? number_format((float) $line['unit_price'], 4).' '.$line['currency'] : 'Missing price' }}</td>
                    <td>{{ number_format((float) ($line['line_total'] ?? 0), 2) }} {{ $line['currency'] ?? data_get($result, 'estimated_value.currency', 'EUR') }}</td>
                    <td><x-supply.human-label :value="$line['price_source'] ?? 'unknown'" /></td>
                    <td>
                        @forelse ($line['warnings'] ?? [] as $warning)
                            <span><x-supply.human-label :value="$warning" /></span>
                        @empty
                            <span>No warnings</span>
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No estimated lines for this subject.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
