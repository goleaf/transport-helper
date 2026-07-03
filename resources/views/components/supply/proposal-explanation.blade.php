@if ($hasFormulaSteps)
    <h3>Formula steps</h3>
    <ol>
        @forelse ($formulaSteps as $step)
            <li>
                @if ($step['is_structured'])
                    <strong>{{ $step['name'] }}</strong>
                    @if ($step['has_formula'])
                        <div>Formula: {{ $step['formula'] }}</div>
                    @endif
                    @if ($step['has_calculation'])
                        <div>Calculation: {{ $step['calculation'] }}</div>
                    @endif
                    @if ($step['has_value'])
                        <div>Value: <x-supply.structured-value :value="$step['value']" /></div>
                    @endif
                @else
                    <x-supply.structured-value :value="$step['raw']" />
                @endif
            </li>
        @empty
            <li>No formula steps.</li>
        @endforelse
    </ol>
@endif

@if ($hasRoundingSteps)
    <h3>Rounding steps</h3>
    <ol>
        @forelse ($roundingSteps as $step)
            <li>
                @if ($step['is_structured'])
                    <strong>{{ $step['name'] }}</strong>
                    @if ($step['has_calculation'])
                        <div>{{ $step['calculation'] }}</div>
                    @endif
                    @if ($step['has_value'])
                        <div>Value: <x-supply.structured-value :value="$step['value']" /></div>
                    @endif
                @else
                    <x-supply.structured-value :value="$step['raw']" />
                @endif
            </li>
        @empty
            <li>No rounding steps.</li>
        @endforelse
    </ol>
@endif

@if ($hasInputValues)
    <h3>Input values</h3>
    <x-supply.structured-value :value="$inputValues" />
@endif

<h3>Detailed explanation</h3>
<x-supply.structured-value :value="$explanation" />
