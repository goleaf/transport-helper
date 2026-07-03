@php
    $formulaSteps = $explanation['formula_steps'] ?? [];
    $roundingSteps = $explanation['rounding_steps'] ?? [];
    $inputValues = $explanation['input_values'] ?? [];
@endphp

@if (is_array($formulaSteps) && count($formulaSteps) > 0)
    <h3>Formula steps</h3>
    <ol>
        @foreach ($formulaSteps as $step)
            <li>
                @if (is_array($step))
                    <strong>{{ $step['name'] ?? 'Step' }}</strong>
                    @if (! empty($step['formula']))
                        <div>Formula: {{ $step['formula'] }}</div>
                    @endif
                    @if (! empty($step['calculation']))
                        <div>Calculation: {{ $step['calculation'] }}</div>
                    @endif
                    @if (array_key_exists('value', $step))
                        <div>Value: {{ is_scalar($step['value']) ? $step['value'] : json_encode($step['value'], JSON_UNESCAPED_SLASHES) }}</div>
                    @endif
                @else
                    {{ is_scalar($step) ? $step : json_encode($step, JSON_UNESCAPED_SLASHES) }}
                @endif
            </li>
        @endforeach
    </ol>
@endif

@if (is_array($roundingSteps) && count($roundingSteps) > 0)
    <h3>Rounding steps</h3>
    <ol>
        @foreach ($roundingSteps as $step)
            <li>
                @if (is_array($step))
                    <strong>{{ $step['name'] ?? 'Rounding' }}</strong>
                    @if (! empty($step['calculation']))
                        <div>{{ $step['calculation'] }}</div>
                    @endif
                    @if (array_key_exists('value', $step))
                        <div>Value: {{ is_scalar($step['value']) ? $step['value'] : json_encode($step['value'], JSON_UNESCAPED_SLASHES) }}</div>
                    @endif
                @else
                    {{ is_scalar($step) ? $step : json_encode($step, JSON_UNESCAPED_SLASHES) }}
                @endif
            </li>
        @endforeach
    </ol>
@endif

@if (is_array($inputValues) && count($inputValues) > 0)
    <h3>Input values</h3>
    <pre>{{ json_encode($inputValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endif

<h3>Raw explanation JSON</h3>
<pre>{{ json_encode($explanation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
