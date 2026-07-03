<?php

namespace App\Enums;

enum FormAutofillRunStatus: string
{
    case Draft = 'draft';
    case AiFilled = 'ai_filled';
    case NeedsReview = 'needs_review';
    case Validated = 'validated';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Exported = 'exported';
    case Failed = 'failed';
}
