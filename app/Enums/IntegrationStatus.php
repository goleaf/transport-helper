<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Draft = 'draft';
    case Configured = 'configured';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Active = 'active';
    case Disabled = 'disabled';
    case Failed = 'failed';
    case Revoked = 'revoked';
}
