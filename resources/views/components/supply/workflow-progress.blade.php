@props(['steps' => []])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="Workflow progress">
    <div class="card-body">
        <h2 class="card-title text-base">Workflow progress</h2>
        <ol class="steps steps-vertical lg:steps-horizontal">
            @forelse ($steps as $step)
                <li class="step {{ $step['class'] }}">{{ $step['label'] }}</li>
            @empty
                <li>No workflow steps configured.</li>
            @endforelse
        </ol>
    </div>
</section>
