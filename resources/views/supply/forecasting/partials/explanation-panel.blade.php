@props(['title' => 'Explanation', 'value' => []])

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Deterministic detail</p>
            <h2>{{ $title }}</h2>
        </div>
    </div>

    <x-supply.structured-value :value="$value" />
</section>
