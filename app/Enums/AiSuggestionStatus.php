<?php

namespace App\Enums;

enum AiSuggestionStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Applied = 'applied';
}
