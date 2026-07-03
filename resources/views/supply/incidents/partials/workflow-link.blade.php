@props(['incident'])

@if ($incident->source_url)
    <a href="{{ $incident->source_url }}">{{ $incident->source_label ?: $incident->source_type }}</a>
@elseif ($incident->source_label || $incident->source_type)
    <span>{{ $incident->source_label ?: $incident->source_type }}</span>
@else
    <span>Manual incident</span>
@endif
