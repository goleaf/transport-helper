<?php

namespace App\Enums;

enum CarrierQuoteStatus: string
{
    case Received = 'received';
    case NeedsReview = 'needs_review';
    case Selected = 'selected';
    case Rejected = 'rejected';
}
