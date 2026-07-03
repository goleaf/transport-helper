<?php

namespace App\Enums;

enum OrderProposalItemStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Adjusted = 'adjusted';
    case Rejected = 'rejected';
}
