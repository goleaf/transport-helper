<?php

namespace App\Enums;

enum OrderProposalStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ConvertedToSupplierOrder = 'converted_to_supplier_order';
}
