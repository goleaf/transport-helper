<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="{{ $title }}">
    <div class="card-body items-start">
        <h2 class="card-title text-base">{{ $title }}</h2>
        @if ($message)
            <p class="text-sm text-slate-600">{{ $message }}</p>
        @endif

        {{ $slot }}

        @if ($hasAction)
            <x-supply.button :href="$actionUrl" variant="primary" mode="solid">
                {{ $actionLabel }}
            </x-supply.button>
        @endif
    </div>
</section>
