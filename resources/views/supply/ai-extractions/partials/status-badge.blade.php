@props(['extraction'])

@if ($extraction->accepted_at)
    <span>Accepted</span>
@elseif ($extraction->rejected_at)
    <span>Rejected</span>
@elseif ($extraction->requires_human_review)
    <span>Needs review</span>
@else
    <span>Reviewed</span>
@endif
