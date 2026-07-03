@props(['extraction'])

@if ($extraction->accepted_at)
    <x-supply.badge class="status-badge" data-status="accepted">Accepted</x-supply.badge>
@elseif ($extraction->rejected_at)
    <x-supply.badge class="status-badge" data-status="rejected">Rejected</x-supply.badge>
@elseif ($extraction->requires_human_review)
    <x-supply.badge class="status-badge" data-status="needs_review">Needs review</x-supply.badge>
@else
    <x-supply.badge class="status-badge" data-status="reviewed">Reviewed</x-supply.badge>
@endif
