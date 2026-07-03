<?php

namespace App\Enums;

enum ProcurementApprovalDecisionType: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
