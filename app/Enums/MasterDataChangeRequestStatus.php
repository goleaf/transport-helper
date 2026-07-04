<?php

namespace App\Enums;

enum MasterDataChangeRequestStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
}
