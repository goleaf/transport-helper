@props(['extraction'])

@if ($extraction->accepted_at)
    <span class="badge badge-outline status-badge" data-status="accepted">Accepted</span>
@elseif ($extraction->rejected_at)
    <span class="badge badge-outline status-badge" data-status="rejected">Rejected</span>
@elseif ($extraction->requires_human_review)
    <span class="badge badge-outline status-badge" data-status="needs_review">Needs review</span>
@else
    <span class="badge badge-outline status-badge" data-status="reviewed">Reviewed</span>
@endif
