<?php

namespace App\Enums;

enum MasterDataMergeStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Executed = 'executed';
    case Cancelled = 'cancelled';
}
