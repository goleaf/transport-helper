<section {{ $attributes->merge(['class' => 'card bg-base-100 border border-base-300 shadow-sm']) }}>
    <div class="card-body">
        {{ $slot }}
    </div>
</section>
