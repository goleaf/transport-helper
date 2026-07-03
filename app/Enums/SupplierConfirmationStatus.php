<?php

namespace App\Enums;

enum SupplierConfirmationStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyConfirmed = 'partially_confirmed';
    case QuantityMismatch = 'quantity_mismatch';
    case DateMismatch = 'date_mismatch';
    case NeedsReview = 'needs_review';
    case Rejected = 'rejected';
}
