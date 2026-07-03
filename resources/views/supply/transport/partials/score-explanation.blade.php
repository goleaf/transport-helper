@php($explanation = is_array($explanation ?? null) ? $explanation : [])
<div>
    <p>{{ $explanation['summary'] ?? 'Not scored yet.' }}</p>
    @if (! empty($explanation['subscores']))
        <ul>
            @foreach ($explanation['subscores'] as $key => $value)
                <li>{{ str($key)->replace('_', ' ')->title() }}: {{ $value }}</li>
            @endforeach
        </ul>
    @endif
    @if (! empty($explanation['penalties']))
        <ul>
            @foreach ($explanation['penalties'] as $penalty)
                <li>{{ $penalty['type'] ?? 'penalty' }} {{ $penalty['points'] ?? '' }}</li>
            @endforeach
        </ul>
    @endif
</div>
