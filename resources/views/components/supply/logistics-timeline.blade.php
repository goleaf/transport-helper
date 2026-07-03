@props([
    'orderDate' => null,
    'confirmationDate' => null,
    'readyDate' => null,
    'pickupDate' => null,
    'deliveryDate' => null,
    'actualReceivedDate' => null,
])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="Logistics timeline">
    <div class="card-body">
        <h2 class="card-title text-base">Logistics timeline</h2>
        <ol class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order date</p>
                <p>{{ $orderDate ?? 'Missing' }}</p>
            </li>
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Confirmation date</p>
                <p>{{ $confirmationDate ?? 'Missing' }}</p>
            </li>
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ready date</p>
                <p>{{ $readyDate ?? 'Missing' }}</p>
            </li>
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pickup date</p>
                <p>{{ $pickupDate ?? 'Missing' }}</p>
            </li>
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Delivery date</p>
                <p>{{ $deliveryDate ?? 'Missing' }}</p>
            </li>
            <li class="rounded-lg border border-base-300 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actual received</p>
                <p>{{ $actualReceivedDate ?? 'Missing' }}</p>
            </li>
        </ol>
    </div>
</section>
