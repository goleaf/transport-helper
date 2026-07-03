<div>
    <p>{{ $summary }}</p>
    @if ($hasSubscores)
        <ul>
            @foreach ($subscores as $subscore)
                <li>{{ $subscore['label'] }}: {{ $subscore['value'] }}</li>
            @endforeach
        </ul>
    @endif
    @if ($hasPenalties)
        <ul>
            @foreach ($penalties as $penalty)
                <li>{{ $penalty['type'] }} {{ $penalty['points'] }}</li>
            @endforeach
        </ul>
    @endif
</div>
